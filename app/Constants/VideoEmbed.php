<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Privacy-preserving embed endpoints for hosted videos. Players load only after
 * the visitor presses play (see App\Support\VideoEmbed and the video facade).
 */
final class VideoEmbed
{
    /** YouTube's cookie-free embed host. */
    public const string YOUTUBE_EMBED = 'https://www.youtube-nocookie.com/embed/';

    public const string VIMEO_EMBED = 'https://player.vimeo.com/video/';

    /** Query string every YouTube embed carries. */
    public const array YOUTUBE_PARAMS = ['autoplay' => 1, 'rel' => 0, 'modestbranding' => 1];

    /** Query string every Vimeo embed carries; dnt=1 switches tracking off. */
    public const array VIMEO_PARAMS = ['dnt' => 1, 'autoplay' => 1];

    /** Hosts recognised as YouTube watch, share or embed links. */
    public const array YOUTUBE_HOSTS = ['youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com'];

    /** Hosts recognised as Vimeo links. */
    public const array VIMEO_HOSTS = ['vimeo.com', 'player.vimeo.com'];

    /** Permissions policy granted to the embed iframe once it loads. */
    public const string IFRAME_ALLOW = 'autoplay; encrypted-media; fullscreen; picture-in-picture';

    /** MIME type by extension for uploaded files whose stored type is unusable. */
    public const array FILE_MIME_TYPES = ['mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg', 'mov' => 'video/quicktime'];

    /** Aspect ratio of every player and thumbnail frame. */
    public const string ASPECT = '16 / 9';

    /** Rendering modes of the public player. */
    public const string KIND_EMBED = 'embed';

    public const string KIND_FILE = 'file';

    public const string KIND_LINK = 'link';
}
