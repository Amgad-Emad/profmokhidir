<?php

namespace App\Models;

use Database\Factories\AnalyticsDailyPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Analytics rollup: one page on one day (views, visitors, engaged seconds,
 * and the sum of each view's deepest scroll mark).
 */
#[Fillable(['day', 'path', 'title', 'section', 'views', 'visitors', 'engaged_seconds', 'scroll_depth_total'])]
class AnalyticsDailyPage extends Model
{
    /** @use HasFactory<AnalyticsDailyPageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
            'views' => 'integer',
            'visitors' => 'integer',
            'engaged_seconds' => 'integer',
            'scroll_depth_total' => 'integer',
        ];
    }
}
