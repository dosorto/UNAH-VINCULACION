<?php

namespace Database\Seeders;

use Database\Seeders\ENF\EnfCatalogoSeeder;
use Database\Seeders\Personal\PermisosSeeder;
use Database\Seeders\Proyecto\VinculacionTiposAccionSeeder;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class LegacyDatabaseMigrationSeeder extends Seeder
{
    /**
     * Estas tablas se transforman o consolidan deliberadamente durante las
     * migraciones. Sus referencias se validan al finalizar mediante las FK.
     */
    private const TRANSFORMED_TABLES = [
        'cache',
        'campus',
        'carrera',
        'centro_facultad',
        'departamento',
        'departamento_academico',
        'ejes_prioritarios_unah',
        'migrations',
        'modalidad',
        'model_has_roles',
        'municipio',
        'pais',
        'permissions',
        'role_has_permissions',
        'roles',
        'vinculacion_tipos_accion_opciones',
    ];

    /** @var array<string, string> */
    private const RENAMED_TABLES = [
        'entidad_contraparte' => 'entidad_contraparte_proyecto',
    ];

    public function run(): void
    {
        $this->command?->warn(
            'Este proceso solo es seguro en una base MySQL vacía. No interrumpa la ejecución.'
        );

        $this->call(LegacyDatabaseDumpSeeder::class);

        $connection = DB::connection();
        $snapshot = $this->snapshotImportedData($connection);

        $this->runMigrations();
        $this->ensureNoPendingMigrations($connection);

        $this->call([
            PermisosSeeder::class,
            VinculacionTiposAccionSeeder::class,
            EnfCatalogoSeeder::class,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->verifyImportedData($connection, $snapshot);
        $foreignKeys = $this->verifyForeignKeyIntegrity($connection);

        $this->command?->newLine();
        $this->command?->info(sprintf(
            'Migración heredada completada: %d tablas y %s filas de origen verificadas; %d claves foráneas sin registros huérfanos.',
            count($snapshot),
            number_format(array_sum(array_column($snapshot, 'count'))),
            $foreignKeys,
        ));
        $this->command?->warn(
            'Desactive LEGACY_DB_IMPORT_ENABLED y ejecute php artisan optimize:clear antes de habilitar la aplicación.'
        );
    }

    private function runMigrations(): void
    {
        $exitCode = Artisan::call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $output = Artisan::output();
        if ($output !== '') {
            $this->command?->getOutput()->write($output);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Falló la ejecución de las migraciones. Recree la base vacía antes de reintentar.'
            );
        }
    }

    private function ensureNoPendingMigrations(ConnectionInterface $connection): void
    {
        $files = array_map(
            static fn (\SplFileInfo $file): string => $file->getBasename('.php'),
            File::files(database_path('migrations')),
        );
        $ran = $connection->table('migrations')->pluck('migration')->all();
        $pending = array_values(array_diff($files, $ran));

        if ($pending !== []) {
            throw new RuntimeException(
                'Quedaron migraciones pendientes: '.implode(', ', $pending)
            );
        }
    }

    /**
     * @return array<string, array{count: int, primary_key: array<int, string>, keys: array<int, string>}>
     */
    private function snapshotImportedData(ConnectionInterface $connection): array
    {
        $database = $connection->getDatabaseName();
        $tables = $connection->select(
            <<<'SQL'
                SELECT TABLE_NAME AS table_name
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            SQL,
            [$database],
        );

        $snapshot = [];

        foreach ($tables as $tableRow) {
            $table = (string) $tableRow->table_name;
            $primaryKey = array_map(
                static fn (object $column): string => (string) $column->column_name,
                $connection->select(
                    <<<'SQL'
                        SELECT COLUMN_NAME AS column_name
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE CONSTRAINT_SCHEMA = ?
                          AND TABLE_NAME = ?
                          AND CONSTRAINT_NAME = 'PRIMARY'
                        ORDER BY ORDINAL_POSITION
                    SQL,
                    [$database, $table],
                ),
            );

            $snapshot[$table] = [
                'count' => (int) $connection->table($table)->count(),
                'primary_key' => $primaryKey,
                'keys' => $this->readKeys($connection, $table, $primaryKey),
            ];
        }

        return $snapshot;
    }

    /**
     * @param  array<string, array{count: int, primary_key: array<int, string>, keys: array<int, string>}>  $snapshot
     */
    private function verifyImportedData(ConnectionInterface $connection, array $snapshot): void
    {
        $database = $connection->getDatabaseName();
        $transformed = array_flip(self::TRANSFORMED_TABLES);

        foreach ($snapshot as $sourceTable => $source) {
            if (isset($transformed[$sourceTable])) {
                continue;
            }

            $targetTable = self::RENAMED_TABLES[$sourceTable] ?? $sourceTable;
            $exists = $connection->selectOne(
                <<<'SQL'
                    SELECT 1 AS present
                    FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = ?
                      AND TABLE_NAME = ?
                      AND TABLE_TYPE = 'BASE TABLE'
                SQL,
                [$database, $targetTable],
            );

            if ($exists === null) {
                if ($source['count'] === 0) {
                    continue;
                }

                throw new RuntimeException(
                    "La tabla '{$sourceTable}' tenía {$source['count']} filas y no existe después de migrar."
                );
            }

            $targetCount = (int) $connection->table($targetTable)->count();
            if ($targetCount < $source['count']) {
                throw new RuntimeException(sprintf(
                    "La tabla '%s' perdió filas durante la migración: %d de origen, %d al finalizar.",
                    $sourceTable,
                    $source['count'],
                    $targetCount,
                ));
            }

            if ($source['primary_key'] === []) {
                continue;
            }

            $targetKeys = array_flip($this->readKeys(
                $connection,
                $targetTable,
                $source['primary_key'],
            ));
            $missingKeys = array_values(array_filter(
                $source['keys'],
                static fn (string $key): bool => ! isset($targetKeys[$key]),
            ));

            if ($missingKeys !== []) {
                throw new RuntimeException(sprintf(
                    "La tabla '%s' perdió %d claves primarias durante la migración.",
                    $sourceTable,
                    count($missingKeys),
                ));
            }
        }
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private function readKeys(
        ConnectionInterface $connection,
        string $table,
        array $columns,
    ): array {
        if ($columns === []) {
            return [];
        }

        return $connection->table($table)
            ->select($columns)
            ->orderBy($columns[0])
            ->get()
            ->map(static function (object $row) use ($columns): string {
                $key = [];

                foreach ($columns as $column) {
                    $key[$column] = $row->{$column};
                }

                return json_encode($key, JSON_THROW_ON_ERROR);
            })
            ->all();
    }

    private function verifyForeignKeyIntegrity(ConnectionInterface $connection): int
    {
        $database = $connection->getDatabaseName();
        $rows = $connection->select(
            <<<'SQL'
                SELECT
                    TABLE_NAME AS child_table,
                    COLUMN_NAME AS child_column,
                    REFERENCED_TABLE_NAME AS parent_table,
                    REFERENCED_COLUMN_NAME AS parent_column,
                    CONSTRAINT_NAME AS constraint_name,
                    ORDINAL_POSITION AS ordinal_position
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION
            SQL,
            [$database],
        );

        $constraints = [];
        foreach ($rows as $row) {
            $key = $row->child_table.'|'.$row->constraint_name;
            $constraints[$key][] = $row;
        }

        $grammar = $connection->getQueryGrammar();

        foreach ($constraints as $columns) {
            $first = $columns[0];
            $join = [];
            $notNull = [];

            foreach ($columns as $column) {
                $childColumn = 'c.'.$grammar->wrap((string) $column->child_column);
                $parentColumn = 'p.'.$grammar->wrap((string) $column->parent_column);
                $join[] = "{$childColumn} = {$parentColumn}";
                $notNull[] = "{$childColumn} IS NOT NULL";
            }

            $parentProbe = 'p.'.$grammar->wrap((string) $columns[0]->parent_column);
            $sql = sprintf(
                'SELECT COUNT(*) AS aggregate FROM %s AS c LEFT JOIN %s AS p ON %s WHERE %s AND %s IS NULL',
                $grammar->wrapTable((string) $first->child_table),
                $grammar->wrapTable((string) $first->parent_table),
                implode(' AND ', $join),
                implode(' AND ', $notNull),
                $parentProbe,
            );

            $orphans = (int) $connection->selectOne($sql)->aggregate;
            if ($orphans > 0) {
                throw new RuntimeException(sprintf(
                    "La relación '%s' de la tabla '%s' tiene %d registros huérfanos.",
                    $first->constraint_name,
                    $first->child_table,
                    $orphans,
                ));
            }
        }

        return count($constraints);
    }
}
