<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class WriteCsvToDisk
{
    public static function run(
        array|Collection $data,
        string $filename,
        ?bool $appendFile = false,
        ?string $disk = 's3',
        array $headerColumns = []
    ): bool {

        if (! str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        // Create a temporary stream for new content
        $newContentStream = fopen('php://temp', 'r+');

        try {
            // Write content to temp stream
            if (! empty($headerColumns)) {
                fputcsv($newContentStream, $headerColumns);
            }

            foreach ($data as $values) {
                fputcsv($newContentStream, $values);
            }

            rewind($newContentStream);

            if ($appendFile) {
                return self::appendToFile($newContentStream, $filename, $disk);
            }

            return Storage::disk($disk)->put($filename, $newContentStream);
        } finally {
            fclose($newContentStream);
        }
    }

    /**
     * Append content to existing file
     */
    private static function appendToFile($newContentStream, string $filename, ?string $disk): bool
    {
        // If file exists, get existing content stream
        if (Storage::disk($disk)->exists($filename)) {
            $existingStream = Storage::disk($disk)->readStream($filename);

            // Create final output stream
            $outputStream = fopen('php://temp', 'r+');

            // Copy existing content in chunks, trimming any extra line endings
            while (! feof($existingStream)) {
                // Trim newlines to avoid double line breaks when appending
                // Each chunk could end with a newline, so we remove them and add a single newline after all chunks
                $chunk = fread($existingStream, 1024 * 1024 * 2); // 2MB chunks
                fwrite($outputStream, rtrim($chunk, "\r\n"));
            }

            // Add a single newline before appending new content
            fwrite($outputStream, PHP_EOL);

            // Append new content
            stream_copy_to_stream($newContentStream, $outputStream);

            rewind($outputStream);
            $success = Storage::disk($disk)->put($filename, $outputStream);

            fclose($existingStream);
            fclose($outputStream);

            return $success;
        }

        // For new files, just write directly
        return Storage::disk($disk)->put($filename, $newContentStream);
    }
}
