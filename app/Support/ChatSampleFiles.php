<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Small, real files of every type the chat accepts, for seeders, tests and the
 * browser QA: a JPEG carrying an EXIF orientation and a GPS location (so
 * metadata stripping can be checked), a PNG, a one-page PDF, Office Open XML
 * documents, a ZIP, a CSV, and an executable disguised under another
 * extension (so content sniffing can be checked). Nothing here is content.
 */
final class ChatSampleFiles
{
    /**
     * A JPEG with EXIF Orientation and GPSLatitude (15° 30' N), $width × $height as stored.
     */
    public static function jpegWithLocation(int $width = 120, int $height = 80, int $orientation = 6): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 0xD3, 0xD1, 0xC5));
        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        // TIFF, little endian: IFD0 (Orientation, GPS pointer) then the GPS IFD (latitude ref and value).
        $tiff = 'II'.pack('v', 42).pack('V', 8)
            .pack('v', 2)
            .pack('vvV', 0x0112, 3, 1).pack('vv', $orientation, 0)
            .pack('vvVV', 0x8825, 4, 1, 38)
            .pack('V', 0)
            .pack('v', 2)
            .pack('vvV', 0x0001, 2, 2).'N'."\0\0\0"
            .pack('vvVV', 0x0002, 5, 3, 68)
            .pack('V', 0)
            .pack('VV', 15, 1).pack('VV', 30, 1).pack('VV', 0, 1);
        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return self::write('location.jpg', substr($jpeg, 0, 2).$app1.substr($jpeg, 2));
    }

    public static function png(int $width = 64, int $height = 48): string
    {
        return PlaceholderImage::grey($width, $height);
    }

    public static function pdf(): string
    {
        return self::write('document.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
    }

    /**
     * An Office Open XML file (docx, xlsx or pptx). Without the content-types part it is only a ZIP.
     */
    public static function ooxml(string $extension = 'docx', bool $withContentTypes = true): string
    {
        $part = ['docx' => 'word/document.xml', 'xlsx' => 'xl/workbook.xml', 'pptx' => 'ppt/presentation.xml'][$extension] ?? 'word/document.xml';
        $path = self::path('document.'.$extension);
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($withContentTypes) {
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        }

        $zip->addFromString($part, '<?xml version="1.0" encoding="UTF-8"?><root/>');
        $zip->close();

        return $path;
    }

    public static function zip(): string
    {
        $path = self::path('archive.zip');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'Placeholder archive.');
        $zip->close();

        return $path;
    }

    public static function csv(): string
    {
        return self::write('table.csv', "year,yield\n1994,2.4\n1995,2.7\n");
    }

    /**
     * A Windows executable header: what a renamed program looks like to content sniffing.
     */
    public static function executable(): string
    {
        return self::write('program.bin', "MZ\x90\0\x03\0\0\0\x04\0\0\0\xFF\xFF\0\0".str_repeat("\0", 64).'This program cannot be run in DOS mode.');
    }

    private static function write(string $name, string $contents): string
    {
        $path = self::path($name);
        file_put_contents($path, $contents);

        return $path;
    }

    private static function path(string $name): string
    {
        $directory = sys_get_temp_dir().'/profmokhidir-chat-samples';

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the sample file directory.');
        }

        return $directory.'/'.bin2hex(random_bytes(4)).'-'.$name;
    }
}
