<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Archive;
use App\Constants\AuthScreens;
use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Support\ResponsiveImage;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * What the sign-in screens show beside the form: the archive portrait on the
 * evergreen panel with its place and year, the epigraph, and the copy of each
 * screen (App\Constants\AuthScreens). The portrait is the published profile's
 * portrait, or failing that a featured archive photograph from a published
 * album; it is cached and cleared whenever the profile or an album is saved.
 */
class AuthPageService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly AlbumRepositoryInterface $albums,
        private readonly MediaRepositoryInterface $media,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * Everything the guest layout needs for one screen.
     *
     * @return array{screen: array{title: string, intro: string, action: string, busy: string}, portrait: Media|null, caption: string|null, epigraph: string, label: string, back: array{label: string, url: string}}
     */
    public function layout(string $screen): array
    {
        $portrait = $this->portrait();

        return [
            'screen' => AuthScreens::screen($screen),
            'portrait' => $portrait,
            'caption' => $portrait === null ? null : ResponsiveImage::placeYear($portrait),
            'epigraph' => __(AuthScreens::EPIGRAPH),
            'label' => __(AuthScreens::PANEL_LABEL),
            'back' => ['label' => __(AuthScreens::BACK_LABEL), 'url' => route('home')],
        ];
    }

    /**
     * The photograph on the evergreen panel (cached): the published portrait
     * first, then the first featured archive photograph of a published album,
     * then any archive photograph. Null when nothing is published yet, in which
     * case the panel shows the epigraph alone.
     */
    public function portrait(): ?Media
    {
        return $this->cache->remember(CacheKeys::AUTH_PORTRAIT, CacheKeys::TTL_SECONDS, function (): ?Media {
            $profile = $this->profiles->currentPublished();

            if ($profile !== null && ($portrait = $this->media->first($profile, MediaCollections::PORTRAIT)) !== null) {
                return $portrait;
            }

            return $this->archivePhoto();
        });
    }

    public function flushCache(): void
    {
        $this->cache->forget(CacheKeys::AUTH_PORTRAIT);
    }

    private function archivePhoto(): ?Media
    {
        $fallback = null;

        foreach ($this->albums->published() as $album) {
            /** @var Album $album */
            foreach ($this->media->inCollection($album, MediaCollections::PHOTOS) as $photo) {
                if ($photo->getCustomProperty(MediaProperties::TREATMENT) !== MediaTreatment::Archive->value) {
                    continue;
                }

                if ((bool) $photo->getAttribute(Archive::FEATURED)) {
                    return $photo;
                }

                $fallback ??= $photo;
            }
        }

        return $fallback;
    }
}
