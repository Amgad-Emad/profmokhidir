<?php

namespace App\Models;

use App\Constants\AnalyticsDimension;
use Database\Factories\AnalyticsDailyTechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Analytics rollup: visitors and page views for one device type, browser
 * family or UTM source (App\Constants\AnalyticsDimension) on one day.
 */
#[Fillable(['day', 'dimension', 'value', 'visitors', 'pageviews'])]
class AnalyticsDailyTechnology extends Model
{
    /** @use HasFactory<AnalyticsDailyTechnologyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
            'dimension' => AnalyticsDimension::class,
            'visitors' => 'integer',
            'pageviews' => 'integer',
        ];
    }
}
