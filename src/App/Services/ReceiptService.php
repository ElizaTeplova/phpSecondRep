<?php

declare(strict_types=1);

namespace App\Services;

use Framework\Database;
use Framework\Exceptions\ValidationException;

class ReceiptService
{

    public function __construct(private Database $db)
    {
    }

    public function validateFile(?array $file) //$file can be null if user submitted data without uploading any files
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException([
                'receipt' => ['Failed to upload file']
            ]);
        }

        $maxFileSizeMB = 3 * 1024 * 1024; //by default in bytes;
        if ($file['size'] > $maxFileSizeMB) {
            throw new ValidationException([
                'receipt' => ["File upload is too large"]
            ]);
        }

        $originalFileName = $file['name'];

        if (!preg_match("/^[A-Za-z0-9\s._-]+$/", $originalFileName)) {
            throw new ValidationException([
                'receipt' => ['Invalid filename']
            ]);
        }

        $clientMimeType = $file['type'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

        if (!in_array($clientMimeType, $allowedMimeTypes)) {
            throw new ValidationException([
                'receipt' => ['Invalid file type']
            ]);
        }
    }
}
