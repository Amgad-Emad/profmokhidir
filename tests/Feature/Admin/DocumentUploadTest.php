<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\TeachingAccess;
use App\Livewire\Admin\Publications\Form as PublicationForm;
use App\Livewire\Admin\TeachingMaterials\Form as TeachingMaterialForm;
use App\Models\AuditLog;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Publication and teaching files: PDF, DOCX, PPTX or ZIP up to 50 MB through
 * the medialibrary collections, with the original name and size shown.
 */
class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
        $this->editor = User::factory()->editor()->create();
    }

    /**
     * A file with a PDF header, so the type and size survive Livewire's temporary upload.
     */
    private static function pdf(string $name, int $kilobytes): UploadedFile
    {
        $header = "%PDF-1.4\n";

        return UploadedFile::fake()->createWithContent($name, $header.str_repeat('a', $kilobytes * 1024 - strlen($header)));
    }

    #[Test]
    public function a_publication_file_is_validated_attached_shown_and_removable(): void
    {
        $publication = Publication::factory()->create();

        $component = Livewire::actingAs($this->editor)->test(PublicationForm::class, ['publication' => $publication])
            ->assertSee('Choose a file')
            ->set('document', UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'))
            ->assertHasErrors(['document'])
            ->assertSee('Use a PDF, Word, PowerPoint or ZIP file.')
            ->set('document', self::pdf('collected-papers.pdf', 640))
            ->assertHasNoErrors()
            ->assertSee('File attached.')
            ->assertSee('collected-papers.pdf')
            ->assertSee('PDF · 640 KB')
            ->assertSee('Replace the file');

        $file = $publication->fresh()->file();
        $this->assertNotNull($file);
        $this->assertSame(MediaCollections::FILE, $file->collection_name);
        $this->assertSame(Disks::PUBLIC, $file->disk);

        $component->call('removeDocument')
            ->assertSee('File removed.')
            ->assertDontSee('collected-papers.pdf');

        $this->assertNull($publication->fresh()->file());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::MEDIA_DELETED)->count());
    }

    #[Test]
    public function a_file_chosen_on_a_new_publication_is_attached_after_the_first_save(): void
    {
        Livewire::actingAs($this->editor)->test(PublicationForm::class)
            ->set('form.title', 'Soils of the Sudan')
            ->set('form.type', 'book')
            ->set('document', UploadedFile::fake()->create('soils.docx', 12, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
            ->assertHasNoErrors()
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $publication = Publication::query()->first();
        $this->assertSame('soils.docx', $publication->file()?->file_name);
    }

    #[Test]
    public function members_only_teaching_files_live_on_the_private_disk_and_link_through_a_signed_url(): void
    {
        $material = TeachingMaterial::factory()->membersOnly()->create();

        Livewire::actingAs($this->editor)->test(TeachingMaterialForm::class, ['teachingMaterial' => $material])
            ->set('document', self::pdf('problem-set.pdf', 2048))
            ->assertHasNoErrors()
            ->assertSee('problem-set.pdf')
            ->assertSee('PDF · 2 MB')
            ->assertSee('signature=', false);

        $file = $material->fresh()->file();
        $this->assertSame(Disks::PRIVATE, $file->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($file->getPathRelativeToRoot());

        // Switching access through the form moves the file to the public disk.
        Livewire::actingAs($this->editor)->test(TeachingMaterialForm::class, ['teachingMaterial' => $material->fresh()])
            ->set('form.access', TeachingAccess::Public->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDontSee('signature=', false);

        $this->assertSame(Disks::PUBLIC, $material->fresh()->file()->disk);
    }
}
