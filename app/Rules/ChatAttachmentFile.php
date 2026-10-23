<?php

declare(strict_types=1);

namespace App\Rules;

use App\Constants\ChatAttachmentKind;
use App\Constants\ChatAttachments;
use App\Constants\ChatCopy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * A chat attachment must have an accepted extension AND content that sniffs
 * as that type (finfo on the bytes, not the browser's claim): a renamed
 * executable is refused. Office Open XML files must also be ZIP archives
 * holding [Content_Types].xml, and images must decode. Size limits are per
 * kind and come from the site settings.
 */
class ChatAttachmentFile implements ValidationRule
{
    public function __construct(
        private readonly int $imageMaxKilobytes,
        private readonly int $documentMaxKilobytes,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The file could not be uploaded. Try again.');

            return;
        }

        $name = $value->getClientOriginalName();
        $extension = strtolower($value->getClientOriginalExtension());
        $kind = ChatAttachments::kindFor($extension);
        $unsupported = strtr(ChatCopy::ATTACH_UNSUPPORTED, [':name' => $name]);

        if ($kind === null) {
            $fail($unsupported);

            return;
        }

        $maxKilobytes = $kind === ChatAttachmentKind::Image ? $this->imageMaxKilobytes : $this->documentMaxKilobytes;

        if ($value->getSize() > $maxKilobytes * 1024) {
            $fail(strtr(ChatCopy::ATTACH_TOO_LARGE, [':name' => $name, ':max' => (string) intdiv($maxKilobytes, 1024)]));

            return;
        }

        $sniffed = strtolower((string) $value->getMimeType());

        if (! in_array($sniffed, ChatAttachments::sniffsFor($extension), true) || ! $this->contentMatches($value, $extension)) {
            $fail($unsupported);
        }
    }

    private function contentMatches(UploadedFile $file, string $extension): bool
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return false;
        }

        if (in_array($extension, ChatAttachments::OOXML_EXTENSIONS, true)) {
            $zip = new ZipArchive;

            if ($zip->open($path, ZipArchive::RDONLY) !== true) {
                return false;
            }

            $ok = $zip->locateName(ChatAttachments::OOXML_MARKER) !== false;
            $zip->close();

            return $ok;
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return @getimagesize($path) !== false;
        }

        if ($extension === 'csv') {
            // Text only: no NUL bytes in the first 8 KB.
            $head = (string) file_get_contents($path, false, null, 0, 8192);

            return ! str_contains($head, "\0");
        }

        return true;
    }
}
