<?php

namespace App\Models;

use App\Constants\DeviceType;
use Database\Factories\AnalyticsEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One page view recorded by the first-party analytics collector, with its
 * engagement (engaged seconds, deepest scroll mark). No IP address or user
 * agent is ever stored: visitor_hash is a daily-salted hash of them. Read and
 * written only through AnalyticsEventRepository; deleted after 30 days.
 */
#[Fillable(['view_id', 'visitor_hash', 'occurred_at', 'path', 'title', 'section', 'referrer_domain', 'country', 'device', 'browser', 'utm_source', 'engaged_seconds', 'scroll_depth'])]
class AnalyticsEvent extends Model
{
    /** @use HasFactory<AnalyticsEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'device' => DeviceType::class,
            'engaged_seconds' => 'integer',
            'scroll_depth' => 'integer',
        ];
    }
}
