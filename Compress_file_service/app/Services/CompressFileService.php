<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CompressFileService
{
    private const TEMP_DIR = 'temp/';

    /*
     * Compress file and save to same source disk and path of the original source file
     */
    public static function run(string $sourceFile, string $sourceDisk = 's3'): string|\Exception
    {
        $zip = new ZipArchive;

        $fileDetails = pathinfo($sourceFile);

        $zipFileName = $fileDetails['filename'] . '.zip';

        // Set destination of zip file to same location as incoming source file
        $zipDestination = ($fileDetails['dirname'] !== '.') ? $fileDetails['dirname'] . '/' . $zipFileName : $zipFileName;

        // Create working directory if it doesn't exist
        if (! Storage::disk('local')->exists(self::TEMP_DIR)) {
            Storage::disk('local')->makeDirectory(self::TEMP_DIR);
        }

        /*
         * ZipArchive needs physical local path to save zip file to.
         * Will save temp zip file to local, move to source disk after creation, then delete local temp file.
         */
        $localZipFile = Storage::disk('local')->path(self::TEMP_DIR . $zipFileName);

        if ($zip->open($localZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $content = Storage::disk($sourceDisk)->get($sourceFile);

            // Add file content to the zip archive without its full path
            $zip->addFromString($fileDetails['basename'], $content);

            $zip->close();

            if (Storage::disk('local')->exists(self::TEMP_DIR . $zipFileName)) {

                // Save zip to source disk
                Storage::disk($sourceDisk)->put($zipDestination, Storage::disk('local')->get(self::TEMP_DIR . $zipFileName));

                // Remove temp zip from local.
                if (Storage::disk('local')->exists(self::TEMP_DIR . $zipFileName)) {
                    Storage::disk('local')->delete(self::TEMP_DIR . $zipFileName);
                }
            }

            return $zipDestination;
        }

        throw new \Exception("File {$sourceFile} could not be compressed");
    }
}
