<?php

namespace Tests\Unit\Rules;

use App\Rules\ChatAttachmentFile;
use App\Support\ChatSampleFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatAttachmentFileTest extends TestCase
{
    private function passes(string $path, string $name, int $imageKb = 10240, int $documentKb = 25600): bool
    {
        $file = new UploadedFile($path, $name, null, null, true);

        return Validator::make(['file' => $file], ['file' => [new ChatAttachmentFile($imageKb, $documentKb)]])->passes();
    }

    /**
     * @return array<string, array{0: callable(): string, 1: string}>
     */
    public static function accepted(): array
    {
        return [
            'jpeg' => [fn () => ChatSampleFiles::jpegWithLocation(), 'photo.jpg'],
            'png' => [fn () => ChatSampleFiles::png(), 'scan.png'],
            'pdf' => [fn () => ChatSampleFiles::pdf(), 'notes.pdf'],
            'docx' => [fn () => ChatSampleFiles::ooxml('docx'), 'essay.docx'],
            'xlsx' => [fn () => ChatSampleFiles::ooxml('xlsx'), 'yields.xlsx'],
            'pptx' => [fn () => ChatSampleFiles::ooxml('pptx'), 'lecture.pptx'],
            'zip' => [fn () => ChatSampleFiles::zip(), 'set.zip'],
            'csv' => [fn () => ChatSampleFiles::csv(), 'table.csv'],
        ];
    }

    #[Test]
    #[DataProvider('accepted')]
    public function every_accepted_type_passes_when_its_content_matches(callable $make, string $name): void
    {
        $this->assertTrue($this->passes($make(), $name));
    }

    #[Test]
    public function a_program_renamed_to_an_accepted_extension_is_refused_by_its_content(): void
    {
        foreach (['report.pdf', 'photo.png', 'photo.jpg', 'table.csv', 'essay.docx', 'set.zip'] as $name) {
            $this->assertFalse($this->passes(ChatSampleFiles::executable(), $name), $name.' must be refused');
        }
    }

    #[Test]
    public function a_plain_zip_renamed_to_docx_is_refused(): void
    {
        $this->assertFalse($this->passes(ChatSampleFiles::ooxml('docx', withContentTypes: false), 'essay.docx'));
    }

    #[Test]
    public function an_image_and_a_document_are_measured_against_their_own_limits(): void
    {
        $image = ChatSampleFiles::jpegWithLocation(400, 300);
        $tiny = (int) ceil(filesize($image) / 1024) - 1;

        $this->assertFalse($this->passes($image, 'photo.jpg', imageKb: max(0, $tiny)));
        $this->assertTrue($this->passes($image, 'photo.jpg', imageKb: 10240, documentKb: 0));
        $this->assertFalse($this->passes(ChatSampleFiles::pdf(), 'notes.pdf', documentKb: 0));
    }

    #[Test]
    public function an_unknown_extension_is_refused(): void
    {
        $this->assertFalse($this->passes(ChatSampleFiles::pdf(), 'notes.exe'));
        $this->assertFalse($this->passes(ChatSampleFiles::png(), 'scan.gif'));
    }
}
