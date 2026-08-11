<?php

namespace Database\Seeders;

use App\Support\Database\MySqlDumpStatementReader;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class LegacyDatabaseDumpSeeder extends Seeder
{
    private const REQUIRED_TABLES = [
        'migrations',
        'users',
        'empleado',
        'proyecto',
    ];

    public function run(MySqlDumpStatementReader $reader): void
    {
        $this->ensureImportIsEnabled();

        $connection = DB::connection();
        $this->ensureCompatibleConnection($connection);
        $this->ensureDatabaseIsEmpty($connection);

        $path = (string) config('legacy-import.dump_path');
        $this->validateDump($path);
        $manifest = $this->inspectDump($reader, $path);

        $this->command?->warn('Importando el dump heredado en una base vacía. Si falla, recree la base antes de reintentar.');

        $session = $connection->selectOne(
            'SELECT @@SESSION.sql_mode AS sql_mode, @@SESSION.foreign_key_checks AS foreign_key_checks, @@SESSION.unique_checks AS unique_checks'
        );

        $createdTables = [];
        $insertStatements = 0;

        try {
            $connection->unprepared("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
            $connection->unprepared('SET SESSION foreign_key_checks = 0');
            $connection->unprepared('SET SESSION unique_checks = 0');
            $connection->unprepared('SET NAMES utf8mb4');

            foreach ($reader->read($path) as $position => $statement) {
                if (preg_match('/^CREATE TABLE `([^`]+)`/i', $statement, $match)) {
                    $this->execute($connection, $statement, $position, $match[1]);
                    $createdTables[] = $match[1];

                    continue;
                }

                if (preg_match('/^INSERT INTO `([^`]+)`/i', $statement, $match)) {
                    $this->execute($connection, $statement, $position, $match[1]);
                    $insertStatements++;
                }
            }

        } finally {
            $connection->statement('SET SESSION foreign_key_checks = '.(int) $session->foreign_key_checks);
            $connection->statement('SET SESSION unique_checks = '.(int) $session->unique_checks);
            $connection->statement('SET SESSION sql_mode = ?', [(string) $session->sql_mode]);
        }

        $this->command?->info(sprintf(
            'Dump importado: %d tablas creadas y %d bloques de datos procesados.',
            count($createdTables),
            $insertStatements,
        ));
        $this->command?->line(sprintf(
            'Manifiesto validado: %d tablas y %d bloques INSERT.',
            count($manifest['tables']),
            $manifest['inserts'],
        ));
        $this->command?->info('El dump quedó listo para aplicar las migraciones del repositorio.');
    }

    private function ensureImportIsEnabled(): void
    {
        if (! config('legacy-import.enabled')) {
            throw new RuntimeException(
                'Importación bloqueada. Configure LEGACY_DB_IMPORT_ENABLED=true para autorizarla explícitamente.'
            );
        }
    }

    private function ensureCompatibleConnection(ConnectionInterface $connection): void
    {
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('El dump solo puede restaurarse sobre MySQL o MariaDB.');
        }
    }

    private function ensureDatabaseIsEmpty(ConnectionInterface $connection): void
    {
        $database = $connection->getDatabaseName();
        $tables = $connection->select(
            <<<'SQL'
                SELECT TABLE_NAME
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_TYPE = 'BASE TABLE'
            SQL,
            [$database],
        );

        if ($tables !== []) {
            throw new RuntimeException(
                "La base de destino '{$database}' no está vacía. No se modificó ninguna tabla."
            );
        }
    }

    private function validateDump(string $path): void
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException(
                'No se encontró un dump legible. Revise LEGACY_DB_DUMP_PATH.'
            );
        }

        $expectedHash = strtolower(trim((string) config('legacy-import.dump_sha256')));

        if ($expectedHash !== '' && ! hash_equals($expectedHash, hash_file('sha256', $path))) {
            throw new RuntimeException('El SHA-256 del dump no coincide con LEGACY_DB_DUMP_SHA256.');
        }
    }

    /**
     * @return array{tables: array<int, string>, inserts: int}
     */
    private function inspectDump(MySqlDumpStatementReader $reader, string $path): array
    {
        $tables = [];
        $inserts = 0;

        foreach ($reader->read($path) as $statement) {
            if (preg_match('/^CREATE TABLE `([^`]+)`/i', $statement, $match)) {
                $tables[] = $match[1];
            } elseif (preg_match('/^INSERT INTO `([^`]+)`/i', $statement)) {
                $inserts++;
            }
        }

        $missingTables = array_diff(self::REQUIRED_TABLES, $tables);

        if ($missingTables !== []) {
            throw new RuntimeException(
                'El dump no contiene las tablas requeridas: '.implode(', ', $missingTables)
            );
        }

        if ($inserts === 0) {
            throw new RuntimeException('El dump no contiene bloques de datos INSERT.');
        }

        return ['tables' => $tables, 'inserts' => $inserts];
    }

    private function execute(
        ConnectionInterface $connection,
        string $statement,
        int $position,
        string $table,
    ): void {
        try {
            $connection->unprepared($statement);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Falló la importación de la tabla '{$table}' cerca de la sentencia {$position}.",
                previous: $exception,
            );
        }
    }
}
// Correo: prueba.migracion@unah.edu.hn
// Contraseña: DevMigration2026!
