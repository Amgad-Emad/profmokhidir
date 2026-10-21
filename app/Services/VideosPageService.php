<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Constants\VideoEmbed as Embed;
use App\Constants\VideoProvider;
use App\Models\Video;
use App\Support\VideoEmbed;
use App\View\Components\ExternalLink;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the Videos section as a lecture hall: a featured recording with a
 * large still at the top, the other recordings below, and the detail page
 * whose player is chosen by provider (cookie-free embed behind a facade, an
 * uploaded file in <video>, or a plain link out) with the recordings from the
 * same event.
 *
 * @phpstan-type Player array{
 *     kind: string,
 *     embed_url: string|null,
 *     file: Media|null,
 *     mime: string|null,
 *     captions_url: string|null,
 *     url: string|null,
 *     host: string,
 *     label: string,
 *     poster: Media|null
 * }
 */
class VideosPageService
{
    public const int DESCRIPTION_LIMIT = 160;

    public function __construct(
        private readonly VideoService $videos,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     featured: Video|null,
     *     player: Player|null,
     *     videos: Collection<int, Video>
     * }
     */
    public function index(): array
    {
        $featured = $this->videos->featured();

        return [
            'section' => $this->navigation->section(Sections::VIDEOS, Sections::VIDEOS),
            'featured' => $featured,
            'player' => $featured === null ? null : $this->player($featured),
            'videos' => $this->videos->published()->reject(fn (Video $video): bool => $video->is($featured))->values(),
        ];
    }

    public function find(string $slug): ?Video
    {
        return $this->videos->findPublishedBySlug($slug);
    }

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     video: Video,
     *     player: Player,
     *     description: string|null,
     *     related: Collection<int, Video>
     * }
     */
    public function show(Video $video): array
    {
        return [
            'section' => $this->navigation->section(Sections::VIDEOS, Sections::VIDEOS.'.show'),
            'video' => $video,
            'player' => $this->player($video),
            'description' => filled($video->description) ? Str::limit(trim((string) $video->description), self::DESCRIPTION_LIMIT) : null,
            'related' => $this->videos->fromSameEvent($video),
        ];
    }

    /**
     * The label line of a recording: event, place and date, in that order.
     *
     * @return list<string>
     */
    public function labelParts(Video $video): array
    {
        return array_values(array_filter([
            filled($video->event) ? trim((string) $video->event) : null,
            filled($video->location) ? trim((string) $video->location) : null,
        ]));
    }

    public function detailUrl(Video $video): string
    {
        return route(Sections::definitions()[Sections::VIDEOS]['route'].'.show', $video->slug);
    }

    /**
     * How the detail page plays this video. A hosted link that cannot be parsed
     * into an embed falls back to a plain link, so nothing is ever lost.
     *
     * @return Player
     */
    public function player(Video $video): array
    {
        $poster = $video->thumbnail();
        $url = filled($video->media_url) ? trim((string) $video->media_url) : null;

        if ($video->provider === VideoProvider::File && $video->videoFile() !== null) {
            $file = $video->videoFile();

            return $this->playerData(Embed::KIND_FILE, file: $file, mime: $this->mimeFor($file), captions: $video->captions_url, poster: $poster);
        }

        $embed = $video->provider->isEmbeddable() ? VideoEmbed::embedUrl($video->provider, $url) : null;

        if ($embed !== null) {
            return $this->playerData(Embed::KIND_EMBED, embedUrl: $embed, captions: $video->captions_url, url: $url, host: ExternalLink::hostOf($url ?? ''), label: 'Watch on '.$video->provider->label(), poster: $poster);
        }

        $host = ExternalLink::hostOf($url ?? '');

        return $this->playerData(Embed::KIND_LINK, captions: $video->captions_url, url: $url, host: $host, label: $host === '' ? 'Watch the video' : 'Watch on '.$host, poster: $poster);
    }

    /**
     * The type given to <source>: the stored type when it is a video type,
     * otherwise a known container type by extension.
     */
    private function mimeFor(Media $file): ?string
    {
        $mime = strtolower((string) $file->mime_type);

        if (str_starts_with($mime, 'video/')) {
            return $mime;
        }

        return Embed::FILE_MIME_TYPES[strtolower(pathinfo((string) $file->file_name, PATHINFO_EXTENSION))] ?? null;
    }

    /**
     * @return Player
     */
    private function playerData(string $kind, ?string $embedUrl = null, ?Media $file = null, ?string $mime = null, ?string $captions = null, ?string $url = null, string $host = '', string $label = '', ?Media $poster = null): array
    {
        return [
            'kind' => $kind,
            'embed_url' => $embedUrl,
            'file' => $file,
            'mime' => $mime,
            'captions_url' => filled($captions) ? trim((string) $captions) : null,
            'url' => $url,
            'host' => $host,
            'label' => $label,
            'poster' => $poster,
        ];
    }
}
