<?php

namespace App\Support;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCsv
{
    public static function download(string $filename, array $headings, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings);

            foreach ($rows() as $row) {
                fputcsv($handle, array_map([self::class, 'formatValue'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function formatValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if (is_bool($value)) {
            return $value ? 'Si' : 'No';
        }

        if (is_iterable($value)) {
            $value = collect($value)->filter()->implode(', ');
        }

        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return 'No especificado';
        }

        return str_replace(["\r\n", "\r", "\n"], ' ', $value);
    }
}
