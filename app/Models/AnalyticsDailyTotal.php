<?php

namespace App\Models;

use Database\Factories\AnalyticsDailyTotalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Analytics rollup: the site-wide figures of one day.
 */
#[Fillable(['day', 'visitors', 'returning_visitors', 'visits', 'pageviews', 'engaged_seconds'])]
class AnalyticsDailyTotal extends Model
{
    /** @use HasFactory<AnalyticsDailyTotalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
            'visitors' => 'integer',
            'returning_visitors' => 'integer',
            'visits' => 'integer',
            'pageviews' => 'integer',
            'engaged_seconds' => 'integer',
        ];
    }
}
