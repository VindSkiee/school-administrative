<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Lightweight structural file validation — no ClamAV needed.
 *
 * Checks:
 * 1. File extension matches actual MIME type (via file header bytes)
 * 2. Rejects polyglots (e.g. PHP code inside JPEG/PDF)
 * 3. Rejects executable extensions
 * 4. Validates file is not empty
 */
class SafeUpload implements ValidationRule
{
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'bat', 'cmd', 'com',
        'exe', 'msi', 'dll', 'scr', 'pif', 'vbs', 'vbe', 'js', 'jse',
        'wsf', 'wsh', 'ps1', 'reg', 'inf', 'hta', 'cpl', 'msp', 'mst',
        'application', 'url', 'lnk', 'svg', 'html', 'htm', 'shtml',
    ];

    private const FILE_SIGNATURES = [
        'pdf' => ['25 50 44 46'],                                          // %PDF
        'jpg' => ['FF D8 FF'],
        'jpeg' => ['FF D8 FF'],
        'png' => ['89 50 4E 47'],                                          // .PNG
        'gif' => ['47 49 46 38'],                                          // GIF8
        'doc' => ['D0 CF 11 E0 A1 B1 1A E1'],                             // OLE2 (legacy .doc)
        'docx' => ['50 4B 03 04'],                                          // ZIP-based (OOXML)
        'pptx' => ['50 4B 03 04'],                                          // ZIP-based (OOXML)
        'xlsx' => ['50 4B 03 04'],                                          // ZIP-based (OOXML)
        'zip' => ['50 4B 03 04'],                                          // ZIP
    ];

    private const MIME_TO_EXTENSION = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
    ];

    public function __construct(
        private array $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'png', 'jpg', 'jpeg'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('validation.uploaded');

            return;
        }

        // 1. Check file extension against blocked list
        $extension = strtolower($value->getClientOriginalExtension());

        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            $fail('File dengan ekstensi .'.$extension.' tidak diizinkan.');

            return;
        }

        // 2. Check extension is in allowed list
        if (! in_array($extension, $this->allowedExtensions, true)) {
            $fail('Ekstensi file .'.$extension.' tidak diizinkan. Yang diizinkan: '.implode(', ', $this->allowedExtensions).'.');

            return;
        }

        // 3. Read file header bytes for MIME sniffing
        $handle = fopen($value->getRealPath(), 'rb');
        if ($handle === false) {
            $fail('Tidak dapat membaca file.');

            return;
        }

        $headerBytes = fread($handle, 8);
        fclose($handle);

        if ($headerBytes === false || $headerBytes === '') {
            $fail('File kosong atau tidak valid.');

            return;
        }

        $hexHeader = strtoupper(bin2hex($headerBytes));

        // 4. Detect polyglots — check if header matches expected MIME
        $detectedType = $this->detectFileType($hexHeader);

        // Special handling for OOXML (docx/pptx/xlsx are ZIP-based)
        $isOoxml = in_array($extension, ['docx', 'pptx', 'xlsx'], true);
        $isZip = $detectedType === 'zip';

        if ($isOoxml && $isZip) {
            // OOXML is ZIP-based, so ZIP header is expected
            return;
        }

        // For ZIP files, allow ZIP header
        if ($extension === 'zip' && $isZip) {
            return;
        }

        // For images and PDF, header must match extension
        $expectedHex = self::FILE_SIGNATURES[$extension] ?? null;
        if ($expectedHex !== null) {
            $matches = false;
            foreach ($expectedHex as $hex) {
                if (str_starts_with($hexHeader, $hex)) {
                    $matches = true;
                    break;
                }
            }

            if (! $matches) {
                $fail('File tampaknya bukan file .'.$extension.' yang valid (header tidak cocok).');

                return;
            }
        }

        // 5. Detect suspicious content — PHP tags in non-PHP files
        if (in_array($extension, ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg', 'jpeg'], true)) {
            $content = file_get_contents($value->getRealPath(), false, null, 0, min($value->getSize(), 8192));
            if ($content !== false && preg_match('/<\?php|<\?=|<\?[\s]/i', $content)) {
                $fail('File mengandung kode PHP yang mencurigakan.');

                return;
            }
        }
    }

    private function detectFileType(string $hexHeader): ?string
    {
        foreach (self::FILE_SIGNATURES as $type => $signatures) {
            foreach ($signatures as $hex) {
                if (str_starts_with($hexHeader, $hex)) {
                    return $type;
                }
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return 'safe_upload';
    }
}
