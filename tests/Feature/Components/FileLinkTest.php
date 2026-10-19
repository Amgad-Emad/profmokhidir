<?php

namespace Tests\Feature\Components;

use App\View\Components\FileLink;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class FileLinkTest extends TestCase
{
    #[Test]
    public function it_shows_type_and_size_and_opens_in_a_new_tab(): void
    {
        $media = new Media(['file_name' => 'paper.pdf', 'mime_type' => 'application/pdf', 'size' => 2150 * 1024]);

        $html = Blade::render('<x-file-link :media="$media" href="/files/paper.pdf" />', ['media' => $media]);

        $this->assertStringContainsString('<a href="/files/paper.pdf" target="_blank" rel="noopener" class="link-meta">Download · PDF · 2.1 MB<span class="sr-only"> (opens in a new tab)</span></a>', $html);
    }

    #[Test]
    public function the_label_can_change_and_the_type_falls_back_to_the_mime_type(): void
    {
        $media = new Media(['file_name' => 'slides', 'mime_type' => 'application/zip', 'size' => 640 * 1024]);

        $html = Blade::render('<x-file-link :media="$media" href="/x" label="Open" />', ['media' => $media]);

        $this->assertStringContainsString('>Open · ZIP · 640 KB<', $html);
        $this->assertSame('DOCX', FileLink::typeOf(new Media(['file_name' => 'notes.docx'])));
        $this->assertSame('File', FileLink::typeOf(new Media(['file_name' => 'blob', 'mime_type' => ''])));
    }
}
