<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Models\Album;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the Pictures section: the album index with covers and photo counts,
 * and each album's grid of captioned photos for the lightbox.
 */
class PicturesPageService
{
    public function __construct(
        private readonly AlbumService $albums,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     albums: list<array{album: Album, cover: Media|null, count: int, url: string}>
     * }
     */
    public function index(): array
    {
        $albums = [];

        foreach ($this->albums->published() as $album) {
            $albums[] = [
                'album' => $album,
                'cover' => $album->cover(),
                'count' => $album->photos()->count(),
                'url' => $this->detailUrl($album),
            ];
        }

        return [
            'section' => $this->navigation->section(Sections::PICTURES, Sections::PICTURES),
            'albums' => $albums,
        ];
    }

    public function find(string $slug): ?Album
    {
        return $this->albums->findPublishedBySlug($slug);
    }

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     album: Album,
     *     photos: MediaCollection<int, Media>,
     *     albums: Collection<int, Album>
     * }
     */
    public function show(Album $album): array
    {
        return [
            'section' => $this->navigation->section(Sections::PICTURES, Sections::PICTURES.'.show'),
            'album' => $album,
            'photos' => $album->photos(),
            'albums' => $this->albums->published()->reject(fn (Album $other): bool => $other->is($album))->values(),
        ];
    }

    public function detailUrl(Album $album): string
    {
        return route(Sections::definitions()[Sections::PICTURES]['route'].'.show', $album->slug);
    }
}
