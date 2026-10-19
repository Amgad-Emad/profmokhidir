<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\VideoEmbed as Hosts;
use App\Constants\VideoProvider;

/**
 * Turns a pasted YouTube or Vimeo link into the privacy-preserving embed URL
 * (youtube-nocookie.com, Vimeo with dnt=1). Returns null for anything it does
 * not recognise, in which case the video is shown as a plain external link.
 */
final class VideoEmbed
{
    public static function embedUrl(VideoProvider $provider, ?string $url): ?string
    {
        return match ($provider) {
            VideoProvider::YouTube => self::youtube($url),
            VideoProvider::Vimeo => self::vimeo($url),
            default => null,
        };
    }

    public static function youtube(?string $url): ?string
    {
        $id = self::youtubeId($url);

        return $id === null ? null : Hosts::YOUTUBE_EMBED.$id.'?'.http_build_query(Hosts::YOUTUBE_PARAMS);
    }

    public static function vimeo(?string $url): ?string
    {
        $parts = self::vimeoId($url);

        if ($parts === null) {
            return null;
        }

        [$id, $hash] = $parts;
        $params = Hosts::VIMEO_PARAMS + ($hash !== null ? ['h' => $hash] : []);

        return Hosts::VIMEO_EMBED.$id.'?'.http_build_query($params);
    }

    /**
     * The 11-character YouTube id from watch, short, live, shorts or embed links.
     */
    public static function youtubeId(?string $url): ?string
    {
        $parsed = self::parse($url, Hosts::YOUTUBE_HOSTS);

        if ($parsed === null) {
            return null;
        }

        [$host, $path, $query] = $parsed;
        $pattern = '/^[A-Za-z0-9_-]{11}$/';

        if (isset($query['v']) && is_string($query['v']) && preg_match($pattern, $query['v'])) {
            return $query['v'];
        }

        $segments = array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));

        if ($host === 'youtu.be') {
            $candidate = $segments[0] ?? '';
        } else {
            $candidate = in_array($segments[0] ?? '', ['embed', 'shorts', 'live', 'v'], true) ? ($segments[1] ?? '') : '';
        }

        return preg_match($pattern, $candidate) ? $candidate : null;
    }

    /**
     * The numeric Vimeo id and, for unlisted videos, the privacy hash.
     *
     * @return array{string, string|null}|null
     */
    public static function vimeoId(?string $url): ?array
    {
        $parsed = self::parse($url, Hosts::VIMEO_HOSTS);

        if ($parsed === null) {
            return null;
        }

        [, $path, $query] = $parsed;
        $segments = array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));

        foreach ($segments as $index => $segment) {
            if (preg_match('/^\d{6,}$/', $segment)) {
                $next = $segments[$index + 1] ?? null;
                $hash = $next !== null && preg_match('/^[a-f0-9]{8,}$/i', $next) ? $next : null;
                $hash ??= isset($query['h']) && is_string($query['h']) && preg_match('/^[a-f0-9]{8,}$/i', $query['h']) ? $query['h'] : null;

                return [$segment, $hash];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $hosts
     * @return array{string, string, array<string, mixed>}|null host, path, query
     */
    private static function parse(?string $url, array $hosts): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host']) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = (string) preg_replace('/^www\./i', '', strtolower($parts['host']));

        if (! in_array($host, $hosts, true)) {
            return null;
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        return [$host, $parts['path'] ?? '', $query];
    }
}
