<?php

namespace Tests\Feature\Requests;

use App\Constants\MediaProperties;
use App\Constants\Permissions;
use App\Constants\Uploads;
use App\Http\Requests\Admin\AlbumRequest;
use App\Http\Requests\Admin\AwardRequest;
use App\Http\Requests\Admin\CommentaryRequest;
use App\Http\Requests\Admin\CvEntryRequest;
use App\Http\Requests\Admin\DocumentUploadRequest;
use App\Http\Requests\Admin\ImageDetailsRequest;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Requests\Admin\OtherItemRequest;
use App\Http\Requests\Admin\ProfileRequest;
use App\Http\Requests\Admin\PublicationRequest;
use App\Http\Requests\Admin\TeachingMaterialRequest;
use App\Http\Requests\Admin\TributeEditRequest;
use App\Http\Requests\Admin\VideoRequest;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminContentRequestsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_content_request_requires_the_manage_content_permission(): void
    {
        $requests = [new ProfileRequest, new CvEntryRequest, new PublicationRequest, new AwardRequest, new VideoRequest, new AlbumRequest, new TeachingMaterialRequest, new CommentaryRequest, new OtherItemRequest, new ImageUploadRequest, new ImageDetailsRequest, new DocumentUploadRequest];

        foreach ($requests as $request) {
            $this->assertTrue($this->as($request, User::factory()->editor()->create())->authorize(), $request::class);
            $this->assertFalse($this->as($request, User::factory()->member()->create())->authorize(), $request::class);
        }

        $this->assertTrue($this->as(new TributeEditRequest, User::factory()->editor()->create())->authorize());
        $this->assertFalse($this->as(new TributeEditRequest, User::factory()->member()->create())->authorize());
        $this->assertTrue(User::factory()->editor()->create()->can(Permissions::MODERATE_TRIBUTES));
    }

    #[Test]
    public function required_fields_enums_years_and_links_are_checked(): void
    {
        $this->assertFails(new AwardRequest, ['status' => 'draft'], ['name']);
        $this->assertFails(new AwardRequest, ['name' => 'X', 'status' => 'live'], ['status']);
        $this->assertFails(new AwardRequest, ['name' => 'X', 'status' => 'draft', 'year' => 1800, 'source_url' => 'ftp://x'], ['year', 'source_url']);
        $this->assertPasses(new AwardRequest, ['name' => 'X', 'status' => 'draft', 'year' => 1999, 'source_url' => 'https://example.org/a']);

        $this->assertFails(new CvEntryRequest, ['section' => 'hobbies', 'title' => 'T', 'status' => 'draft', 'start_year' => 1990, 'end_year' => 1980], ['section', 'end_year']);
        $this->assertFails(new PublicationRequest, ['title' => 'T', 'type' => 'poem', 'status' => 'draft'], ['type']);
        $this->assertFails(new VideoRequest, ['title' => 'T', 'provider' => 'youtube', 'status' => 'draft'], ['media_url']);
        $this->assertPasses(new VideoRequest, ['title' => 'T', 'provider' => 'file', 'status' => 'draft']);
        $this->assertFails(new TeachingMaterialRequest, ['title' => 'T', 'type' => 'slides', 'access' => 'everyone', 'status' => 'draft'], ['access']);
        $this->assertFails(new CommentaryRequest, ['title' => 'T', 'status' => 'draft', 'slug' => 'Bad Slug'], ['slug']);
        $this->assertFails(new OtherItemRequest, ['title' => 'T', 'status' => 'draft', 'destination_url' => 'nope'], ['destination_url']);
        $this->assertFails(new ProfileRequest, ['name' => 'N', 'status' => 'draft', 'contact_email_public' => 'not-an-email', 'key_facts' => [['label' => str_repeat('a', 61), 'value' => 'v']]], ['contact_email_public', 'key_facts.0.label']);
        $this->assertFails(new TributeEditRequest, ['name' => '', 'message' => str_repeat('a', 2001)], ['name', 'message']);
    }

    #[Test]
    public function slugs_must_be_unique_ignoring_the_record_itself_and_trashed_records(): void
    {
        $existing = Publication::factory()->create(['slug' => 'taken']);
        $trashed = Publication::factory()->create(['slug' => 'freed']);
        $trashed->delete();

        $this->assertFails(new PublicationRequest, ['title' => 'T', 'type' => 'book', 'status' => 'draft', 'slug' => 'taken'], ['slug']);
        $this->assertPasses(new PublicationRequest($existing->getKey()), ['title' => 'T', 'type' => 'book', 'status' => 'draft', 'slug' => 'taken']);
        $this->assertPasses(new PublicationRequest, ['title' => 'T', 'type' => 'book', 'status' => 'draft', 'slug' => 'freed']);
    }

    #[Test]
    public function uploads_are_limited_by_type_and_size(): void
    {
        $this->assertFails(new DocumentUploadRequest, ['document' => UploadedFile::fake()->create('a.exe', 10)], ['document']);
        $this->assertFails(new DocumentUploadRequest, ['document' => UploadedFile::fake()->create('a.pdf', Uploads::DOCUMENT_MAX_KILOBYTES + 1, 'application/pdf')], ['document']);
        $this->assertPasses(new DocumentUploadRequest, ['document' => UploadedFile::fake()->create('a.zip', 1000, 'application/zip')]);

        $this->assertFails(new ImageUploadRequest, ['image' => UploadedFile::fake()->create('a.gif', 10, 'image/gif')], ['image']);
        $this->assertFails(new ImageUploadRequest, ['image' => UploadedFile::fake()->image('a.jpg')->size(Uploads::IMAGE_MAX_KILOBYTES + 1)], ['image']);
        $this->assertPasses(new ImageUploadRequest, ['image' => UploadedFile::fake()->image('a.png', 10, 10), 'treatment' => 'archive']);
    }

    #[Test]
    public function image_details_are_optional_unless_a_caller_requires_them(): void
    {
        $this->assertSame([], Validator::make(['treatment' => 'archive'], ImageDetailsRequest::rulesFor(false))->errors()->keys());

        $errors = Validator::make(['treatment' => 'archive'], ImageDetailsRequest::rulesFor(true))->errors()->keys();
        $this->assertEqualsCanonicalizing(MediaProperties::details(), $errors);

        $this->assertSame(['treatment'], Validator::make(['treatment' => 'sepia'], ImageDetailsRequest::rulesFor(false))->errors()->keys());
    }

    private function as(FormRequest $request, User $user): FormRequest
    {
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     */
    private function assertFails(FormRequest $request, array $data, array $fields): void
    {
        $errors = Validator::make($data, $request->rules(), $request->messages(), $request->attributes())->errors();

        foreach ($fields as $field) {
            $this->assertTrue($errors->has($field), sprintf('%s should fail on %s; got %s', $request::class, $field, $errors->toJson()));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertPasses(FormRequest $request, array $data): void
    {
        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($validator->passes(), sprintf('%s should pass; got %s', $request::class, $validator->errors()->toJson()));
    }
}
