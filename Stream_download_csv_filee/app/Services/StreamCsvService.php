<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamCsvService
{
    public function run(array|Collection $data, array $columns, string $filename): StreamedResponse
    {
        if (empty($filename)) {
            $filename = 'csv_file_' . time() . '.csv';
        }

        if (! str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        $callback = function () use ($data, $columns) {
            // Write to the output buffer
            $fp = fopen('php://output', 'wb');

            fputcsv($fp, $columns);

            foreach ($data as $values) {
                fputcsv($fp, $values);
            }
            fclose($fp);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
