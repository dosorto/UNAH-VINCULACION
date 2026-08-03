# Importación de la base de Vinculación

## Resultado del análisis

El dump `dump-db_vinculacion-202608030913.sql` corresponde a MySQL 8 y contiene:

- 109 tablas;
- 64 bloques `INSERT`;
- 9,114 filas en total, incluidas 1,912 entradas de `activity_log`;
- 358 usuarios, 354 empleados, 63 proyectos y sus relaciones;
- migraciones aplicadas hasta `2026_04_16_074500_increase_poblacion_participante_precision_on_proyecto_table`.
- 82 migraciones del repositorio pendientes de aplicar;
- 3 migraciones SGCU registradas en el dump que ya no están en el repositorio; sus tablas permanecen en el dump y no bloquean las migraciones nuevas.

El dump es anterior a los módulos y cambios de esquema agregados entre mayo y agosto de 2026. Por eso no debe restaurarse encima de una base que ya tenga las migraciones actuales. La secuencia compatible es:

1. crear una base MySQL vacía y apuntar temporalmente la aplicación hacia ella;
2. importar el dump con `LegacyDatabaseDumpSeeder`;
3. ejecutar todas las migraciones pendientes;
4. validar los conteos y relaciones antes de cambiar la conexión de producción.

Las tablas operativas del dump (`cache`, `sessions`, `jobs`) también se restauran para mantener una copia fiel. Deben vaciarse después de validar la migración si la base se utilizará como el nuevo ambiente productivo.

## Configuración

El dump contiene información productiva y no debe agregarse a Git. Cópielo al servidor en una ruta privada, por ejemplo:

```text
storage/app/private/imports/db_vinculacion.sql
```

Configure temporalmente:

```dotenv
LEGACY_DB_IMPORT_ENABLED=true
LEGACY_DB_DUMP_PATH=/ruta/absoluta/db_vinculacion.sql
LEGACY_DB_DUMP_SHA256=ade4c0cc85c00e0896c6865595722536898643a6de9da9f7f249bed388cb9ea6
```

Después de apuntar `DB_DATABASE` a una base completamente vacía:

```bash
php artisan config:clear
php artisan db:seed --class='Database\Seeders\LegacyDatabaseDumpSeeder' --force
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\Personal\PermisosSeeder' --force
php artisan db:seed --class='Database\Seeders\Proyecto\VinculacionTiposAccionSeeder' --force
php artisan db:seed --class='Database\Seeders\ENF\EnfCatalogoSeeder' --force
php artisan permission:cache-reset
php artisan optimize:clear
```

El seeder se detiene antes de modificar datos si encuentra cualquier tabla en la base destino. También omite todas las instrucciones `DROP TABLE` del dump.

No ejecute `DatabaseSeeder` completo sobre los datos importados: algunos seeders históricos crean registros repetidos o reconstruyen relaciones existentes. Los seeders indicados arriba sincronizan los permisos requeridos por la aplicación y completan los catálogos nuevos que no existen en el dump.

## Validaciones posteriores

Ejecute como mínimo:

```sql
SELECT COUNT(*) FROM users;                 -- esperado: 358
SELECT COUNT(*) FROM empleado;              -- esperado: 354
SELECT COUNT(*) FROM proyecto;              -- esperado: 63
SELECT COUNT(*) FROM actividades;           -- esperado: 126
SELECT COUNT(*) FROM actividad_empleado;    -- esperado: 267
SELECT COUNT(*) FROM model_has_roles;        -- esperado: 374
SELECT COUNT(*) FROM model_has_permissions;  -- esperado: 490
```

Revise además:

- que `php artisan migrate:status` no muestre migraciones pendientes;
- que usuarios, empleados y proyectos conserven sus relaciones;
- que las migraciones de contrapartes, flujos, informes y ENF hayan finalizado;
- que los archivos referenciados en `firma_sello_empleado` y anexos existan también en el almacenamiento migrado.

Cuando finalice la importación, vuelva a configurar:

```dotenv
LEGACY_DB_IMPORT_ENABLED=false
```
