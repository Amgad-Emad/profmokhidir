<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PictureView;
use App\Constants\Sections;
use App\Models\Album;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the Pictures section as a museum archive: the album index as an
 * exhibition (or, switched by ?view=decades, every photograph through the
 * decades) and each album as an exhibition wall with museum labels and the
 * object view.
 *
 * @phpstan-import-type Hung from PhotoArchiveService
 */
class PicturesPageService
{
    public function __construct(
        private readonly AlbumService $albums,
        private readonly PhotoArchiveService $archive,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     view: PictureView,
     *     views: list<array{label: string, url: string, active: bool}>,
     *     albums: list<array{album: Album, cover: Media|null, count: int, range: string|null, url: string}>,
     *     decades: list<array{decade: int|null, label: string, count: int, photos: list<Hung>}>,
     *     total: int
     * }
     */
    public function index(PictureView $view = PictureView::Albums): array
    {
        $albums = [];
        $total = 0;

        foreach ($this->albums->published() as $album) {
            $count = $album->photos()->count();
            $total += $count;

            $albums[] = [
                'album' => $album,
                'cover' => $album->cover(),
                'count' => $count,
                'range' => $this->archive->yearRange($album),
                'url' => $this->detailUrl($album),
            ];
        }

        return [
            'section' => $this->navigation->section(Sections::PICTURES, Sections::PICTURES),
            'view' => $view,
            'views' => $this->views($view),
            'albums' => $albums,
            'decades' => $view === PictureView::Decades ? $this->archive->decades() : [],
            'total' => $total,
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
     *     range: string|null,
     *     wall: list<Hung>,
     *     albums: Collection<int, Album>
     * }
     */
    public function show(Album $album): array
    {
        return [
            'section' => $this->navigation->section(Sections::PICTURES, Sections::PICTURES.'.show'),
            'album' => $album,
            'range' => $this->archive->yearRange($album),
            'wall' => $this->archive->wall($album),
            'albums' => $this->albums->published()->reject(fn (Album $other): bool => $other->is($album))->values(),
        ];
    }

    public function detailUrl(Album $album): string
    {
        return route(Sections::definitions()[Sections::PICTURES]['route'].'.show', $album->slug);
    }

    public function indexUrl(PictureView $view = PictureView::Albums): string
    {
        return route(Sections::definitions()[Sections::PICTURES]['route'], $view->query());
    }

    /**
     * The two ways of looking at the archive, as links that remember the choice in the URL.
     *
     * @return list<array{label: string, url: string, active: bool}>
     */
    private function views(PictureView $current): array
    {
        $views = [];

        foreach (PictureView::cases() as $view) {
            $views[] = ['label' => $view->label(), 'url' => $this->indexUrl($view), 'active' => $view === $current];
        }

        return $views;
    }
}
