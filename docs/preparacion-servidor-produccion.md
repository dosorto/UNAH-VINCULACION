# Preparación del servidor de producción

Guía operativa para preparar, desplegar y verificar NEXO en un servidor de producción.
Los comandos deben ejecutarse desde la raíz del proyecto y con el usuario apropiado.

## 1. Requisitos del servidor

- Linux con PHP 8.3 o la versión soportada por el proyecto.
- Extensiones PHP requeridas por Laravel, MySQL, manejo de archivos, XML, Mbstring,
  cURL, GD y ZIP.
- Composer, Node.js/NPM, MySQL o MariaDB y Supervisor.
- LibreOffice y `pdfinfo` para los procesos que generan o validan documentos.
- HTTPS, DNS configurado y un servidor web (Nginx o Apache) apuntando a `public/`.
- El usuario del servidor web debe poder escribir en `storage/` y `bootstrap/cache`,
  pero no debe tener permisos de escritura sobre el código fuente.

## 2. Código y dependencias

Actualizar el código únicamente desde una rama revisada y aprobada:

```bash
git fetch --all --prune
git checkout devury
git pull --ff-only origin devury
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

No se deben versionar ni copiar al repositorio el `.env`, contraseñas, llaves
privadas, dumps de la base de datos ni archivos privados de los usuarios.

## 3. Variables de entorno

Crear el `.env` de producción con valores reales y secretos administrados de forma
segura. Como mínimo, revisar estas variables:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio.example
APP_KEY=base64:GENERAR_CON_php_artisan

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vinculacion
DB_USERNAME=nexo
DB_PASSWORD=CAMBIAR_POR_UN_SECRETO

SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example
MAIL_PORT=587
MAIL_USERNAME=usuario-smtp
MAIL_PASSWORD=CAMBIAR_POR_UN_SECRETO
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example
MAIL_FROM_NAME="NEXO"
```

Generar la llave únicamente si el entorno todavía no tiene una:

```bash
php artisan key:generate --force
```

Después de importar una base legada, `LEGACY_DB_IMPORT_ENABLED` debe quedar en
`false`. La importación inicial se documenta en
[`importacion-base-legada.md`](importacion-base-legada.md).

## 4. Base de datos y migraciones

Antes de migrar, realizar un respaldo verificable de la base de producción.
Luego ejecutar:

```bash
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
php artisan migrate:status
```

La segunda consulta no debe mostrar migraciones pendientes. Confirmar especialmente
que esté aplicada la migración `2026_09_22_000001_allow_retiring_workflow_stages`,
porque agrega la configuración necesaria para las etapas retirables del flujo.

No importar un dump sobre una base que ya contiene datos de producción. Para una
base legada nueva, seguir el procedimiento completo de
[`importacion-base-legada.md`](importacion-base-legada.md) y no ejecutar el
`DatabaseSeeder` completo sobre datos importados.

## 5. Archivos y permisos

```bash
php artisan storage:link
mkdir -p storage/logs storage/framework/cache storage/framework/sessions
mkdir -p storage/framework/views storage/app/private
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Los PDFs de constancias se guardan en `storage/app/private`. Deben descargarse
mediante las rutas autorizadas de la aplicación; no se debe publicar directamente
esa carpeta.

## 6. Worker de colas y constancias

Las constancias se crean inicialmente como `PENDIENTE`. El job
`GenerarPdfConstanciaRegistroProyecto` genera el archivo y cambia el estado a
`EMITIDA` únicamente cuando el procesamiento termina correctamente. Sin un worker
activo, la constancia permanecerá pendiente aunque el flujo haya finalizado.

Instalar la configuración de Supervisor y ajustar `APP_PATH` y `QUEUE_USER`:

```bash
cp deploy/supervisor/nexo-queue.conf.example \
   /etc/supervisor/conf.d/nexo-queue.conf
```

Recargar y verificar el proceso:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart nexo-queue
sudo supervisorctl status nexo-queue
```

El worker equivalente, para una comprobación controlada, es:

```bash
php artisan queue:work database \
  --sleep=3 --tries=3 --timeout=120 --backoff=10
```

En producción debe mantenerse un solo mecanismo de administración del worker;
si Supervisor lo gestiona, no dejar otro worker manual permanente en paralelo.

Comandos de diagnóstico:

```bash
php artisan queue:failed
php artisan queue:retry all
tail -f storage/logs/worker.log
tail -f storage/logs/laravel.log
```

`queue:retry all` debe ejecutarse solo después de revisar la causa del fallo y
confirmar que el proceso puede reintentarse sin duplicar efectos externos.

## 7. Correo y notificaciones del flujo

Las notificaciones del flujo también pueden estar encoladas. Verificar el SMTP con
una cuenta de prueba y confirmar que:

- el remitente esté autorizado por el proveedor de correo;
- el worker esté procesando los jobs;
- los destinatarios correspondan al creador, encargados y responsables de la etapa;
- no se registren contraseñas ni tokens en `storage/logs`.

## 8. Cachés después del despliegue

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart nexo-queue
```

Si el servidor usa PHP-FPM con OPcache, reiniciar el servicio según la distribución
para asegurar que se cargue el código desplegado.

## 9. Verificación posterior al despliegue

Ejecutar:

```bash
php artisan about
php artisan migrate:status
php artisan queue:failed
sudo supervisorctl status nexo-queue
```

Validar manualmente, en un entorno controlado o con un registro de prueba:

1. Inicio de sesión y permisos de los perfiles involucrados.
2. Creación o adaptación de un proyecto al flujo.
3. Avance de cada etapa y asignación de responsables.
4. Envío de notificaciones al cambiar de etapa y al subsanar.
5. Identificación del creador del proyecto para respuestas y subsanaciones.
6. Aprobación final y generación de la constancia.
7. Descarga de la constancia y apertura del PDF generado.

Para detectar constancias detenidas:

```sql
SELECT id, proyecto_id, estado, ruta_archivo, created_at
FROM constancias_registro_proyecto
WHERE estado IN ('PENDIENTE', 'ERROR')
ORDER BY id DESC;
```

Si existen registros `PENDIENTE` y jobs en la tabla `jobs`, revisar Supervisor y
`storage/logs/worker.log`. Si el estado es `ERROR`, revisar el error de Laravel y
la dependencia que genera el PDF antes de reintentar.

## 10. Respaldos y operación

- Respaldar la base antes de cada migración y conservar una copia fuera del servidor.
- Respaldar `storage/app/private` junto con la base para conservar documentos.
- Probar periódicamente la restauración de ambos respaldos.
- Vigilar `storage/logs`, `jobs` y `failed_jobs`.
- No borrar jobs fallidos ni archivos privados sin identificar su causa y conservar
  un respaldo.
- Registrar la versión desplegada, la fecha, las migraciones ejecutadas y el
  resultado de las pruebas posteriores.

Para el detalle de la configuración del worker, consultar
[`colas-produccion.md`](colas-produccion.md).
