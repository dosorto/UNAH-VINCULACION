# Importación de la base de Vinculación

## Resultado del análisis

El dump `dump-db_vinculacion-11-08-2026.sql`, generado el 11 de agosto de 2026
a las 08:21, corresponde a MySQL 8 y contiene:

- 109 tablas;
- 64 bloques `INSERT`;
- 11,738 filas en total, incluidas 4,581 entradas de `activity_log`;
- 358 usuarios, 354 empleados, 63 proyectos y sus relaciones;
- migraciones aplicadas hasta `2026_04_16_074500_increase_poblacion_participante_precision_on_proyecto_table`.
- 85 migraciones del repositorio pendientes de aplicar;
- 3 migraciones SGCU registradas en el dump que ya no están en el repositorio; sus tablas permanecen en el dump y no bloquean las migraciones nuevas.

La importación de prueba terminó con 182 tablas, 158 migraciones registradas y
ninguna relación huérfana en las 343 claves foráneas verificadas. Los conteos de
las tablas de negocio se conservaron. Las migraciones sí consolidan registros
duplicados en catálogos geográficos y académicos, pero antes reasignan todas sus
referencias. En particular, las 50 filas heredadas de contrapartes se conservan
en `entidad_contraparte_proyecto` y quedan vinculadas a 41 entidades únicas en
el catálogo `entidad_contraparte`.

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
LEGACY_DB_DUMP_SHA256=4495213e9934f750d9d3fa676fa3bb5b48fb4d138d8db186787526403a1a10df
```

Después de apuntar `DB_DATABASE` a una base completamente vacía, el seeder
orquestador importa todas las tablas y datos del dump, ejecuta las migraciones,
sincroniza únicamente los catálogos compatibles y valida conteos, claves
primarias y relaciones:

```bash
php artisan config:clear
php artisan db:seed --class='Database\Seeders\LegacyDatabaseMigrationSeeder' --force
php artisan optimize:clear
```

El seeder se detiene antes de modificar datos si encuentra cualquier tabla en
la base destino. También omite todas las instrucciones `DROP TABLE` del dump.
Si una importación o migración falla, recree la base vacía antes de reintentar;
no ejecute el seeder una segunda vez sobre la base parcialmente migrada.

No ejecute `DatabaseSeeder` completo sobre los datos importados: algunos
seeders históricos crean registros repetidos o reconstruyen relaciones
existentes. El orquestador ejecuta internamente solo los tres seeders seguros
que sincronizan permisos y completan los catálogos que no existen en el dump.

## Validaciones posteriores

Ejecute como mínimo:

```sql
SELECT COUNT(*) FROM users;                 -- esperado: 358
SELECT COUNT(*) FROM empleado;              -- esperado: 354
SELECT COUNT(*) FROM proyecto;              -- esperado: 63
SELECT COUNT(*) FROM actividades;           -- esperado: 126
SELECT COUNT(*) FROM actividad_empleado;    -- esperado: 267
SELECT COUNT(*) FROM activity_log;           -- esperado: 4581
SELECT COUNT(*) FROM estudiante_proyecto;    -- esperado: 35
SELECT COUNT(*) FROM firma_proyecto;         -- esperado: 81
SELECT COUNT(*) FROM model_has_roles;        -- esperado: 375
SELECT COUNT(*) FROM model_has_permissions;  -- esperado: 490
SELECT COUNT(*) FROM entidad_contraparte_proyecto; -- esperado: 50
SELECT COUNT(*) FROM entidad_contraparte;    -- esperado: 41 entidades únicas
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
