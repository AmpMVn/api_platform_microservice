# GKE
```shell
kubectl create secret generic rentsoft-ms-online-booking-postgres-secret \
--from-literal=dbName=api \
--from-literal=dbUserNameKey=api \
--from-literal=dbPasswordKey=api
```
