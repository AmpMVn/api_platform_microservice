FROM php:8.0-fpm

COPY ./docker/php-fpm/wait-for-it.sh /usr/bin/wait-for-it

RUN chmod +x /usr/bin/wait-for-it

RUN apt-get update && \
    apt-get install -y --no-install-recommends libssl-dev zlib1g-dev curl git unzip netcat libxml2-dev libpq-dev libzip-dev cron libfcgi-bin wget && \
    pecl install apcu && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install -j$(nproc) zip opcache intl pdo_pgsql pgsql mysqli && \
    docker-php-ext-enable apcu pdo_pgsql mysqli sodium && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    cron

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENV memory_limit=2048M
RUN echo "memory_limit = ${memory_limit}" >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini

RUN echo "opcache.preload = /var/www/config/preload.php" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.preload_user = www-data" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.memory_consumption = 256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.max_accelerated_files = 20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.validate_timestamps = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini


RUN wget -O /usr/local/bin/php-fpm-healthcheck \
	https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
	&& chmod +x /usr/local/bin/php-fpm-healthcheck


RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin/ --filename=composer

WORKDIR ~

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www
RUN rm -rf /var/www/html

RUN composer config --global --auth http-basic.repo.packagist.com MVn 4496b17515dd3b51471763b1c035170932f2e939235a2fb2cc2e1aa01b61

#RUN composer install --no-interaction --no-progress

#RUN chown -R www-data:www-data ./

RUN echo "*/5 * * * * root php /var/www/html/article/bin/console do:sync:old:update >> /var/log/cron.log 2>&1" >> /etc/crontab

RUN touch /var/log/cron.log
RUN cron

RUN set -xe && echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

ENTRYPOINT ["docker-php-entrypoint"]

EXPOSE 9000

CMD ["php-fpm"]
