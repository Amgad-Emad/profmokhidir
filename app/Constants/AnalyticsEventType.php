<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * What an analytics beacon reports: a page view (sent once when the page is
 * shown) or the engagement of that view so far (sent whenever the tab is
 * hidden or the page is left: engaged seconds and the deepest scroll mark).
 */
enum AnalyticsEventType: string
{
    use HasValues;

    case Pageview = 'pageview';
    case Engagement = 'engagement';

    public function label(): string
    {
        return match ($this) {
            self::Pageview => __('Page view'),
            self::Engagement => __('Engagement'),
        };
    }
}
