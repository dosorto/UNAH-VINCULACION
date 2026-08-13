<?php

namespace Tests\Unit;

use App\Support\Database\MySqlDumpStatementReader;
use PHPUnit\Framework\TestCase;

class MySqlDumpStatementReaderTest extends TestCase
{
    public function test_reads_multiline_statements_and_preserves_semicolons_inside_values(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mysql-dump-reader-');

        file_put_contents($path, <<<'SQL'
            -- MySQL dump
            CREATE TABLE `users` (
              `id` bigint NOT NULL,
              `name` varchar(255) NOT NULL
            );
            INSERT INTO `users` VALUES
            (1,'Nombre; con punto y coma'),
            (2,'Texto con \'comillas\'');
            SQL);

        try {
            $statements = iterator_to_array((new MySqlDumpStatementReader)->read($path));
        } finally {
            unlink($path);
        }

        $this->assertCount(2, $statements);
        $this->assertStringStartsWith('CREATE TABLE `users`', $statements[0]);
        $this->assertStringContainsString("(1,'Nombre; con punto y coma')", $statements[1]);
        $this->assertStringContainsString("(2,'Texto con \\'comillas\\'')", $statements[1]);
    }

    public function test_rejects_an_incomplete_statement(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mysql-dump-reader-');
        file_put_contents($path, 'CREATE TABLE `users` (`id` bigint)');

        try {
            $this->expectExceptionMessage('sentencia SQL incompleta');
            iterator_to_array((new MySqlDumpStatementReader)->read($path));
        } finally {
            unlink($path);
        }
    }
}
