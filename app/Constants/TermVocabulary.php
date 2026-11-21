<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * The short labels editors type once and reuse to group or filter content
 * (an Other Information category, a teaching course, a commentary tag). The
 * stored value is the English term and stays the key for grouping, filters
 * and addresses; its name in another language is a term translation.
 */
enum TermVocabulary: string
{
    use HasValues;

    case OtherCategory = 'other_category';
    case OtherType = 'other_type';
    case TeachingCourse = 'teaching_course';
    case TeachingTopic = 'teaching_topic';
    case CommentaryTag = 'commentary_tag';
    case PublicationTopic = 'publication_topic';

    public function label(): string
    {
        return match ($this) {
            self::OtherCategory => __('Other information: categories'),
            self::OtherType => __('Other information: types'),
            self::TeachingCourse => __('Teaching materials: courses'),
            self::TeachingTopic => __('Teaching materials: topics'),
            self::CommentaryTag => __('Commentary: topics'),
            self::PublicationTopic => __('Publications: topics'),
        };
    }

    /**
     * The English wording shown for a stored term. Other Information types are
     * typed as keys ("press") and shown in title case ("Press").
     */
    public function english(string $term): string
    {
        return $this === self::OtherType ? \Illuminate\Support\Str::headline($term) : $term;
    }
}
