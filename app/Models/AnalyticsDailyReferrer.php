<?php

namespace App\Models;

use Database\Factories\AnalyticsDailyReferrerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Analytics rollup: visitors and page views from one referring domain on one
 * day (an empty domain for direct visits).
 */
#[Fillable(['day', 'referrer_domain', 'visitors', 'pageviews'])]
class AnalyticsDailyReferrer extends Model
{
    /** @use HasFactory<AnalyticsDailyReferrerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
            'visitors' => 'integer',
            'pageviews' => 'integer',
        ];
    }
}
