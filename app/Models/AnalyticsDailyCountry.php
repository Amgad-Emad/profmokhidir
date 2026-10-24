<?php

namespace App\Models;

use Database\Factories\AnalyticsDailyCountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Analytics rollup: visitors and page views from one country on one day.
 */
#[Fillable(['day', 'country', 'visitors', 'pageviews'])]
class AnalyticsDailyCountry extends Model
{
    /** @use HasFactory<AnalyticsDailyCountryFactory> */
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
