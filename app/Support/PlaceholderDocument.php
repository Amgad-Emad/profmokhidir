<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Generates a one-page placeholder PDF for seeders and tests, so file links
 * ("PDF · 2 KB") and the public/private disk split can be exercised locally.
 */
final class PlaceholderDocument
{
    /**
     * Write a minimal, valid PDF carrying the label and return its path.
     */
    public static function pdf(string $label = 'Placeholder'): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $label);
        $stream = sprintf('BT /F1 18 Tf 72 770 Td (%s) Tj ET', $text);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($stream), $stream),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $index + 1, $body);
        }

        $xref = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n0000000000 65535 f \n", count($objects) + 1);

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n", count($objects) + 1, $xref);

        $directory = sys_get_temp_dir().'/profmokhidir-placeholders';

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the placeholder document directory.');
        }

        $path = sprintf('%s/placeholder-%s.pdf', $directory, bin2hex(random_bytes(4)));
        file_put_contents($path, $pdf);

        return $path;
    }
}
