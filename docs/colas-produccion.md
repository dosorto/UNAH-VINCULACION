# Colas en producción

Las constancias de registro se crean en estado `PENDIENTE` y su PDF se genera
mediante un Job en la cola `database`. En producción debe mantenerse activo un
worker; de lo contrario, el registro queda pendiente aunque la solicitud haya
terminado correctamente.

## Supervisor

1. Definir las variables `APP_PATH` y `QUEUE_USER` en el entorno del servidor.
2. Copiar `deploy/supervisor/nexo-queue.conf.example` a
   `/etc/supervisor/conf.d/nexo-queue.conf`.
3. Recargar Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart nexo-queue
```

Verificar el proceso:

```bash
sudo supervisorctl status nexo-queue
tail -f storage/logs/worker.log
```

Para procesar trabajos pendientes después de instalar el worker:

```bash
php artisan queue:work database --once
```

Para revisar trabajos fallidos:

```bash
php artisan queue:failed
```
