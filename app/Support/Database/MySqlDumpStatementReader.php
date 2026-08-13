<?php

namespace App\Support\Database;

use Generator;
use RuntimeException;

final class MySqlDumpStatementReader
{
    /**
     * Read SQL statements without loading the complete dump into memory.
     *
     * @return Generator<int, string>
     */
    public function read(string $path): Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el dump: {$path}");
        }

        $statement = '';
        $inSingleQuote = false;
        $escaped = false;

        try {
            while (($line = fgets($handle)) !== false) {
                if (trim($statement) === '' && $this->isIgnorableLine($line)) {
                    $statement = '';

                    continue;
                }

                $length = strlen($line);

                for ($index = 0; $index < $length; $index++) {
                    $character = $line[$index];

                    if ($statement === '' && ctype_space($character)) {
                        continue;
                    }

                    $statement .= $character;

                    if ($inSingleQuote) {
                        if ($escaped) {
                            $escaped = false;
                        } elseif ($character === '\\') {
                            $escaped = true;
                        } elseif ($character === "'") {
                            $inSingleQuote = false;
                        }

                        continue;
                    }

                    if ($character === "'") {
                        $inSingleQuote = true;

                        continue;
                    }

                    if ($character === ';') {
                        $sql = trim($statement);
                        $statement = '';

                        if ($sql !== '') {
                            yield $sql;
                        }
                    }
                }
            }

            if (trim($statement) !== '') {
                throw new RuntimeException('El dump termina con una sentencia SQL incompleta.');
            }
        } finally {
            fclose($handle);
        }
    }

    private function isIgnorableLine(string $line): bool
    {
        $line = ltrim($line);

        return $line === ''
            || $line === "\n"
            || $line === "\r\n"
            || str_starts_with($line, '--');
    }
}
