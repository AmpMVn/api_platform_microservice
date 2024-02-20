# Author: MVm <michal.vanek@rentsoft.de>
# Cluster: rs-ms-article-a


### ConfigMap Nginx
apiVersion: v1
kind: ConfigMap
metadata:
  name: rs-ms-article-nginx-config
  labels:
    app: rs-ms-article-nginx
data:
  nginx.conf: |
    # you must set worker processes based on your CPU cores, nginx does not benefit from setting more than that
    worker_processes auto; #some last versions calculate it automatically

    # number of file descriptors used for nginx
    # the limit for the maximum FDs on the server is usually set by the OS.
    # if you don't set FD's then OS settings will be used which is by default 2000
    worker_rlimit_nofile 100000;

    # only log critical errors
    error_log /var/log/nginx/error.log crit;

    events {
      # determines how much clients will be served per worker
      # max clients = worker_connections * worker_processes
      # max clients is also limited by the number of socket connections available on the system (~64k)
      worker_connections 4000;

      # optimized to serve many clients with each thread, essential for linux -- for testing environment
      use epoll;

      # accept as many connections as possible, may flood worker connections if set too low -- for testing environment
      multi_accept on;
    }
    http {
      # cache informations about FDs, frequently accessed files
      # can boost performance, but you need to test those values
      open_file_cache max=200000 inactive=20s;
      open_file_cache_valid 30s;
      open_file_cache_min_uses 2;
      open_file_cache_errors on;

      # to boost I/O on HDD we can disable access logs
      access_log off;

      # copies data between one FD and other from within the kernel
      # faster than read() + write()
      sendfile on;

      # send headers in one piece, it is better than sending them one by one
      tcp_nopush on;

      # don't buffer data sent, good for small data bursts in real time
      tcp_nodelay on;

      # reduce the data that needs to be sent over network -- for testing environment
      gzip on;
      # gzip_static on;
      gzip_min_length 10240;
      gzip_comp_level 1;
      gzip_vary on;
      gzip_disable msie6;
      gzip_proxied expired no-cache no-store private auth;
      gzip_types
        # text/html is always compressed by HttpGzipModule
        text/css
        text/javascript
        text/xml
        text/plain
        text/x-component
        application/javascript
        application/x-javascript
        application/json
        application/xml
        application/rss+xml
        application/atom+xml
        font/truetype
        font/opentype
        application/vnd.ms-fontobject
        image/svg+xml;

      # allow the server to close connection on non responding client, this will free up memory
      reset_timedout_connection on;

      # request timed out -- default 60
      client_body_timeout 10;

      # if client stop responding, free up memory -- default 60
      send_timeout 2;

      # server will close connection after this time -- default 75
      keepalive_timeout 30;

      # number of requests client can make over keep-alive -- for testing environment
      keepalive_requests 100000;

      server {
        listen 80 default_server;
        listen [::]:80 default_server;
        index index.php index.html;
        error_log  /var/log/nginx/error.log;
        access_log /var/log/nginx/access.log;

        # Set nginx to serve files from the shared volume!
        root /var/www/html/article/public;
        server_name _;
        location / {
          try_files $uri $uri/ /index.php$is_args$args;
          gzip_static on;
          include  /etc/nginx/mime.types;
        }
        location ~ \.php$ {
          try_files $uri =404;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          fastcgi_pass localhost:9000;
          fastcgi_index index.php;
          include fastcgi_params;
          fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
          fastcgi_param PATH_INFO $fastcgi_path_info;

          fastcgi_buffer_size 32k;
          fastcgi_buffers 8 16k;

           proxy_read_timeout 1800;
           proxy_connect_timeout 1800;
           proxy_send_timeout 1800;
           send_timeout 1800;
        }
      }
    }
---
### KSA
apiVersion: v1
kind: ServiceAccount
metadata:
  name: rs-ms-article-ksa
---
### API
apiVersion: apps/v1
kind: Deployment
metadata:
  name: rs-ms-article
  labels:
    app: rs-ms-article
spec:
  #  minReadySeconds: 30
  selector:
    matchLabels:
      app: rs-ms-article
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxUnavailable: 0
      maxSurge: 20
  template:
    metadata:
      labels:
        app: rs-ms-article
    spec:
      containers:
        - name: rs-ms-article-csp
          # It is recommended to use the latest version of the Cloud SQL proxy
          # Make sure to update on a regular schedule!
          image: gcr.io/cloudsql-docker/gce-proxy
          command:
            - "/cloud_sql_proxy"
            - "-instances=x0-marketing:europe-west3:prod-rs-ms-11=tcp:5432"
            - "-term_timeout=60s"
          securityContext:
            runAsNonRoot: true
          resources:
            requests:
              memory: 512Mi
              cpu: 0.25
        - name: rs-ms-article-php   # php container with installed symfony app
          image: gcr.io/GOOGLE_CLOUD_PROJECT/rentsoft-ms-article-php-fpm-api:COMMIT_SHA
          env: # get environment variables from secrets
            - name: APP_ENV
              value: "prod"
            - name: APP_DEBUG
              value: "false"
            - name: APP_SECRET
              value: "a988bb42aea10eb7a71eca57b8954039"
          ports:
            - containerPort: 9000
              name: ms-api-9000
            - containerPort: 8000
              name: ms-api-8000
          livenessProbe:
            exec:
              command:
                - php-fpm-healthcheck
                - --listen-queue=10
            initialDelaySeconds: 0
            periodSeconds: 10
          readinessProbe:
            exec:
              command:
                - php-fpm-healthcheck
            initialDelaySeconds: 1
            periodSeconds: 10
          lifecycle:
            postStart: # commands that will be executed after container was created
              exec:
                command: #generate key, wait-for-it 127.0.0.1:5432 -s -- bin/console doctrine:migrations:migrate;
                  - "sh"
                  - "-c"
                  - >
                    cp -R /var/www/article/ /var/www/html/;
                    chown -R www-data:www-data /var/www/html;
                    chmod 755 /var/www/html;
                    php /var/www/html/article/bin/console doctrine:migrations:migrate -n;
                    cron;
          volumeMounts:
            # mount volume for communication with nginx
            - name: rs-ms-article-sf-api
              mountPath: /var/www/html/article/
          resources:
            requests:
              cpu: 0.5
              memory: 1024Mi
        - name: rs-ms-article-nginx   # php container with installed symfony app
          image: gcr.io/GOOGLE_CLOUD_PROJECT/rentsoft-ms-article-nginx-api:COMMIT_SHA
          ports:
            - containerPort: 80
          livenessProbe:
            failureThreshold: 3
            httpGet:
              path: /health
              port: 80
              scheme: HTTP
            initialDelaySeconds: 60
            periodSeconds: 60
            successThreshold: 1
            timeoutSeconds: 10
          readinessProbe:
            failureThreshold: 3
            httpGet:
              path: /health
              port: 80
              scheme: HTTP
            initialDelaySeconds: 60
            periodSeconds: 60
            successThreshold: 1
            timeoutSeconds: 10
          volumeMounts:
            - name: rs-ms-article-sf-api
              mountPath: /var/www/html/article/
            - name: rs-ms-article-nginx-config-volume # mount volume for nginx config
              mountPath: /etc/nginx/nginx.conf
              subPath: nginx.conf
          resources:
            requests:
              cpu: 0.25
              memory: 512Mi
      volumes:
        - name: rs-ms-article-sf-api
          emptyDir: { }
        - name: rs-ms-article-nginx-config-volume
          configMap:
            name: rs-ms-article-nginx-config
      serviceAccountName: rs-ms-article-ksa
---
apiVersion: autoscaling/v2beta2
kind: HorizontalPodAutoscaler
metadata:
  name: rs-ms-article-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: rs-ms-article
  minReplicas: 1
  maxReplicas: 3
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 85
---
apiVersion: v1
kind: Service
metadata:
  name: rs-ms-article
  labels:
    app: rs-ms-article
spec:
  type: NodePort
  ports:
    - port: 80
      name: rs-ms-article-nginx
    - port: 9000
      name: rs-ms-article-php
  selector:
    app: rs-ms-article
---
### SSL
apiVersion: networking.gke.io/v1
kind: ManagedCertificate
metadata:
  name: rs-ms-article-cert
spec:
  domains:
    - article.ms.rentsoft.de
---
### Ingress
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: rs-ms-article-ing
  annotations:
    kubernetes.io/ingress.global-static-ip-name: rs-ms-article-ip
    kubernetes.io/ingress.class: "gce"
    networking.gke.io/managed-certificates: rs-ms-article-cert
  labels:
    app: rs-ms-article
spec:
  defaultBackend:
    service:
      name: rs-ms-article
      port:
        number: 80
