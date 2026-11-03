<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\ListDensity;
use Database\Factories\ListPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One user's last-used view of one admin list (`screen` is its
 * AdminNavigation key): `state` holds the search, filters, sort and tab as they
 * stood in the address; `density` the row spacing chosen there; `columns` the data table's
 * columns the user showed or hid (key => bool) and `per_page` its page size.
 */
#[Fillable(['user_id', 'screen', 'state', 'density', 'columns', 'per_page'])]
class ListPreference extends Model
{
    /** @use HasFactory<ListPreferenceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => 'array',
            'density' => ListDensity::class,
            'columns' => 'array',
            'per_page' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
