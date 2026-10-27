<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Profile;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\Locales;
use App\Constants\MediaCollections;
use App\Constants\MediaTreatment;
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

    /**
     * The one profile is created on its first save and updated afterwards.
     */
    protected function store(array $data): Model
    {
        $profile = app(ProfileService::class)->save($data, auth()->user());
        $this->recordId = (int) $profile->getKey();
        $this->form = $this->fromRecord($profile);
        $this->loadImages();

        return $profile;
    }

    protected function redirectsAfterCreate(): bool
    {
        return false;
    }

    protected function createdMessage(): string
    {
        return __('Profile saved.');
    }

    /**
     * Add an empty key fact row, in English or in a translation.
     */
    public function addKeyFact(?string $locale = null): void
    {
        $facts = $this->keyFacts($locale);
        $facts[] = ['label' => '', 'value' => ''];
        $this->setKeyFacts($locale, $facts);
    }

    public function removeKeyFact(int $index, ?string $locale = null): void
    {
        $facts = $this->keyFacts($locale);
        unset($facts[$index]);
        $this->setKeyFacts($locale, array_values($facts));
    }

    protected function transform(array $validated): array
    {
        return $this->cleanLists(parent::transform($validated));
    }

    protected function transformTranslation(array $validated, string $locale): array
    {
        $data = $this->cleanLists(parent::transformTranslation($validated, $locale));

        // An empty Arabic list is no translation: visitors see the English one.
        foreach (['expertise', 'key_facts'] as $list) {
            if (($data[$list] ?? null) === []) {
                $data[$list] = null;
            }
        }

        return $data;
    }

    /**
     * Expertise as trimmed non-empty phrases; key facts without empty rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function cleanLists(array $data): array
    {
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

    /**
     * @return list<array{label: string, value: string}>
     */
    private function keyFacts(?string $locale): array
    {
        $facts = $this->isTranslation($locale)
            ? ($this->form[self::TRANSLATIONS][$locale]['key_facts'] ?? [])
            : ($this->form['key_facts'] ?? []);

        return is_array($facts) ? array_values($facts) : [];
    }

    /**
     * @param  list<array{label: string, value: string}>  $facts
     */
    private function setKeyFacts(?string $locale, array $facts): void
    {
        if ($this->isTranslation($locale)) {
            $this->form[self::TRANSLATIONS][$locale]['key_facts'] = $facts;
        } else {
            $this->form['key_facts'] = $facts;
        }
    }

    private function isTranslation(?string $locale): bool
    {
        return $locale !== null && in_array($locale, Locales::prefixed(), true);
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
