<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = DB::connection();
$database = $connection->getDatabaseName();

$tables = collect($connection->select(
    "select table_name as table_name
     from information_schema.tables
     where table_schema = ? and table_type = 'BASE TABLE'
     order by table_name",
    [$database]
))->pluck('table_name')->all();

$columns = collect($connection->select(
    "select table_name as table_name,
            column_name as column_name,
            column_type as column_type,
            data_type as data_type,
            is_nullable as is_nullable,
            column_key as column_key,
            column_default as column_default,
            extra as extra,
            column_comment as column_comment
     from information_schema.columns
     where table_schema = ?
     order by table_name, ordinal_position",
    [$database]
))->groupBy('table_name');

$indexes = collect($connection->select(
    "select table_name as table_name,
            index_name as index_name,
            non_unique as non_unique,
            column_name as column_name,
            seq_in_index as seq_in_index
     from information_schema.statistics
     where table_schema = ?
     order by table_name, index_name, seq_in_index",
    [$database]
))->groupBy('table_name');

$foreignKeys = collect($connection->select(
    "select constraint_name as constraint_name,
            table_name as table_name,
            column_name as column_name,
            referenced_table_name as referenced_table_name,
            referenced_column_name as referenced_column_name,
            ordinal_position as ordinal_position
     from information_schema.key_column_usage
     where table_schema = ?
       and referenced_table_name is not null
     order by table_name, constraint_name, ordinal_position",
    [$database]
))->groupBy('constraint_name');

function dbmlType(object $column): string
{
    $type = strtolower($column->column_type);
    $dataType = strtolower($column->data_type);

    if ($dataType === 'tinyint' && preg_match('/tinyint\(1\)/', $type)) {
        return 'boolean';
    }

    if (str_starts_with($type, 'enum(')) {
        return 'varchar';
    }

    $type = preg_replace('/\s+unsigned/', '', $type);
    $type = preg_replace('/\s+zerofill/', '', $type);

    return match ($dataType) {
        'char', 'varchar' => preg_replace('/\s+.*$/', '', $type),
        'tinytext', 'mediumtext', 'longtext' => 'text',
        'tinyblob', 'mediumblob', 'longblob' => 'blob',
        'datetime', 'timestamp', 'date', 'time', 'year', 'json', 'text', 'blob' => $dataType,
        default => $type,
    };
}

function dbmlValue(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $upper = strtoupper((string) $value);
    if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()', 'NULL'], true)) {
        return $upper === 'NULL' ? null : '`' . $value . '`';
    }

    if (is_numeric($value)) {
        return (string) $value;
    }

    return "'" . str_replace("'", "\\'", (string) $value) . "'";
}

function dbmlNote(?string $comment, object $column): ?string
{
    $notes = [];

    if ($comment) {
        $notes[] = $comment;
    }

    if (str_starts_with(strtolower($column->column_type), 'enum(')) {
        $notes[] = $column->column_type;
    }

    if ($notes === []) {
        return null;
    }

    return "'" . str_replace("'", "\\'", implode(' | ', $notes)) . "'";
}

function columnList(array $columns): string
{
    return count($columns) === 1 ? $columns[0] : '(' . implode(', ', $columns) . ')';
}

$lines = [
    '// ============================================================',
    '// UNAH-VINCULACION - Database Diagram',
    '// Generado automaticamente desde la base configurada en Laravel',
    '// Base: ' . $database,
    '// Tablas: ' . count($tables),
    '// ============================================================',
    '',
];

foreach ($tables as $table) {
    $lines[] = "Table {$table} {";

    foreach ($columns->get($table, collect()) as $column) {
        $settings = [];

        if ($column->column_key === 'PRI') {
            $settings[] = 'pk';
        }

        if (str_contains(strtolower($column->extra ?? ''), 'auto_increment')) {
            $settings[] = 'increment';
        }

        $settings[] = $column->is_nullable === 'YES' ? 'null' : 'not null';

        if (($default = dbmlValue($column->column_default)) !== null) {
            $settings[] = 'default: ' . $default;
        }

        if (($note = dbmlNote($column->column_comment, $column)) !== null) {
            $settings[] = 'note: ' . $note;
        }

        $suffix = $settings === [] ? '' : ' [' . implode(', ', $settings) . ']';
        $lines[] = '  ' . $column->column_name . ' ' . dbmlType($column) . $suffix;
    }

    $tableIndexes = $indexes->get($table, collect())
        ->reject(fn ($index) => $index->index_name === 'PRIMARY')
        ->groupBy('index_name');

    if ($tableIndexes->isNotEmpty()) {
        $lines[] = '';
        $lines[] = '  indexes {';

        foreach ($tableIndexes as $indexName => $indexColumns) {
            $orderedColumns = $indexColumns
                ->sortBy('seq_in_index')
                ->pluck('column_name')
                ->all();

            $options = [];
            if ((int) $indexColumns->first()->non_unique === 0) {
                $options[] = 'unique';
            }

            if (!str_starts_with($indexName, implode('_', $orderedColumns))) {
                $options[] = "name: '{$indexName}'";
            }

            $suffix = $options === [] ? '' : ' [' . implode(', ', $options) . ']';
            $lines[] = '    ' . columnList($orderedColumns) . $suffix;
        }

        $lines[] = '  }';
    }

    $lines[] = '}';
    $lines[] = '';
}

foreach ($foreignKeys as $constraintName => $keys) {
    $ordered = $keys->sortBy('ordinal_position');
    $fromColumns = $ordered->pluck('column_name')->all();
    $toColumns = $ordered->pluck('referenced_column_name')->all();
    $fromTable = $ordered->first()->table_name;
    $toTable = $ordered->first()->referenced_table_name;

    $lines[] = "Ref {$constraintName}: {$fromTable}." . columnList($fromColumns)
        . " > {$toTable}." . columnList($toColumns);
}

echo rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
