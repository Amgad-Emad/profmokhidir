<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Profile;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaTreatment;
use App\Exceptions\ImagesNotReadyException;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\ProfileRequest;
use App\Livewire\Admin\Concerns\ManagesImages;
use App\Livewire\Admin\ContentForm;
use App\Services\AbstractContentService;
use App\Services\ProfileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The single profile (About page): created on first save, edited afterwards.
 */
class EditProfile extends ContentForm
{
    use ManagesImages;

    public function mount(): void
    {
        $this->initialise(app(ProfileService::class)->current());
        $this->loadImages();
    }

    protected function type(): string
    {
        return ContentTypes::PROFILE;
    }

    protected function service(): AbstractContentService
    {
        return app(ProfileService::class);
    }

    protected function request(): ContentRequest
    {
        return new ProfileRequest;
    }

    protected function defaults(): array
    {
        return [
            'name' => '',
            'title' => null,
            'affiliation' => null,
            'short_bio' => null,
            'full_bio' => null,
            'expertise' => [],
            'key_facts' => [],
            'students_supervised' => null,
            'contact_email_public' => null,
            'contact_note' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.profile.edit-profile';
    }

    protected function viewData(): array
    {
        return $this->imagesViewData();
    }

    public function save(): void
    {
        $validated = $this->validate()['form'] ?? [];

        try {
            $profile = app(ProfileService::class)->save($this->transform($validated), auth()->user());
            $this->recordId = (int) $profile->getKey();
            $this->form = $this->fromRecord($profile);
            $this->loadImages();
            $this->notify('Profile saved.');
        } catch (ImagesNotReadyException $exception) {
            $this->addError(self::FIELD_PREFIX.'status', $exception->getMessage());
        }
    }

    public function addKeyFact(): void
    {
        $this->form['key_facts'][] = ['label' => '', 'value' => ''];
    }

    public function removeKeyFact(int $index): void
    {
        unset($this->form['key_facts'][$index]);
        $this->form['key_facts'] = array_values($this->form['key_facts']);
    }

    protected function transform(array $validated): array
    {
        $data = parent::transform($validated);

        $data['expertise'] = array_values(array_filter(array_map(
            fn ($item): string => trim((string) $item),
            is_array($data['expertise'] ?? null) ? $data['expertise'] : [],
        )));

        $data['key_facts'] = array_values(array_filter(array_map(
            fn ($fact): array => ['label' => trim((string) ($fact['label'] ?? '')), 'value' => trim((string) ($fact['value'] ?? ''))],
            is_array($data['key_facts'] ?? null) ? $data['key_facts'] : [],
        ), fn (array $fact): bool => $fact['label'] !== '' || $fact['value'] !== ''));

        return $data;
    }

    protected function fromRecord(Model $record): array
    {
        $form = parent::fromRecord($record);
        $form['expertise'] = is_array($record->expertise) ? array_values($record->expertise) : [];
        $form['key_facts'] = is_array($record->key_facts) ? array_values($record->key_facts) : [];

        return $form;
    }

    protected function imageCollection(): string
    {
        return MediaCollections::PORTRAIT;
    }

    /**
     * The portrait always needs alt text, caption, place and year, draft or not.
     */
    protected function imageDetailsRequired(Model $record): bool
    {
        return true;
    }

    protected function defaultTreatment(): MediaTreatment
    {
        return MediaTreatment::Portrait;
    }

    protected function attachImage(Model $record, UploadedFile $file, array $details): Media
    {
        return app(ProfileService::class)->setPortrait($record, $file, $details);
    }
}
