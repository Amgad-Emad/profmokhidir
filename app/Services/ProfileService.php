<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Profile;
use App\Models\User;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Services\Concerns\GuardsImagePublishing;
use App\Support\RichText;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends AbstractContentService<Profile>
 */
class ProfileService extends AbstractContentService
{
    use GuardsImagePublishing;

    public function __construct(
        ProfileRepositoryInterface $profiles,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($profiles, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::PROFILE;
    }

    /**
     * The sign-in screens show the portrait too, so they are cleared with the profile.
     *
     * @return list<string>
     */
    protected function cacheKeys(): array
    {
        return [CacheKeys::PROFILE, CacheKeys::AUTH_PORTRAIT];
    }

    /**
     * The published profile for the public site (cached).
     */
    public function get(): ?Profile
    {
        /** @var ProfileRepositoryInterface $repository */
        $repository = $this->repository;

        return $this->remember($this->cacheKey(), fn (): ?Profile => $repository->currentPublished());
    }

    /**
     * The profile row for the admin form, published or not.
     */
    public function current(): ?Profile
    {
        /** @var ProfileRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository->current();
    }

    /**
     * Create on first save, update afterwards.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?User $actor = null): Profile
    {
        $existing = $this->current();

        return $existing === null ? $this->create($data, $actor) : $this->update($existing, $data, $actor);
    }

    protected function guardedImageCollections(): array
    {
        return [MediaCollections::PORTRAIT];
    }

    /**
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function setPortrait(Profile $profile, string|UploadedFile $file, array $properties = []): Media
    {
        $properties[MediaProperties::TREATMENT] ??= MediaTreatment::Portrait->value;

        $media = $this->media->attach($profile, $file, MediaCollections::PORTRAIT, $properties);
        $this->flushCache();

        return $media;
    }

    public function removePortrait(Profile $profile, ?User $actor = null): void
    {
        $portrait = $this->media->first($profile, MediaCollections::PORTRAIT);

        if ($portrait !== null) {
            $this->media->remove($portrait, $actor);
        }

        $this->flushCache();
    }

    protected function prepare(array $data, $existing): array
    {
        if (array_key_exists('full_bio', $data)) {
            $data['full_bio'] = $this->eachLocale($data['full_bio'], fn (mixed $bio): ?string => RichText::sanitise(is_string($bio) ? $bio : null) ?: null);
        }

        foreach (['expertise', 'key_facts'] as $list) {
            if (array_key_exists($list, $data)) {
                $data[$list] = $this->eachLocale($data[$list], fn (mixed $items): mixed => is_array($items) ? array_values($items) : $items);
            }
        }

        if (array_key_exists('contact_email_public', $data) && is_string($data['contact_email_public'])) {
            $email = mb_strtolower(trim($data['contact_email_public']));
            $data['contact_email_public'] = $email === '' ? null : $email;
        }

        return $data;
    }
}
