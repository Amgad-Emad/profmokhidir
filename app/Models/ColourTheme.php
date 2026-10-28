<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ColourThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A colour theme (Admin › Appearance): the colours chosen for the light and
 * the dark mode (token => hex; unset tokens derive, see
 * App\Services\ThemePaletteService), unpublished edits in the draft columns.
 * Presets are the read-only suggested templates (App\Constants\ThemePresets).
 */
#[Table('themes')]
#[Fillable(['name', 'slug', 'is_active', 'is_preset', 'based_on', 'light', 'dark', 'draft_light', 'draft_dark', 'created_by'])]
class ColourTheme extends Model
{
    /** @use HasFactory<ColourThemeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_preset' => 'boolean',
            'light' => 'array',
            'dark' => 'array',
            'draft_light' => 'array',
            'draft_dark' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasDraft(): bool
    {
        return $this->draft_light !== null || $this->draft_dark !== null;
    }

    /**
     * The colours being edited: the draft when there is one, else the published colours.
     *
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function working(): array
    {
        return [
            'light' => (array) ($this->draft_light ?? $this->light ?? []),
            'dark' => (array) ($this->draft_dark ?? $this->dark ?? []),
        ];
    }
}
