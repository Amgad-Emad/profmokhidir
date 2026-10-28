<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ColourTheme;
use App\Repositories\Contracts\ColourThemeRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentColourThemeRepository implements ColourThemeRepositoryInterface
{
    public function __construct(private readonly ColourTheme $model) {}

    public function find(int $id): ?ColourTheme
    {
        return $this->query()->find($id);
    }

    public function findBySlug(string $slug): ?ColourTheme
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function active(): ?ColourTheme
    {
        return $this->query()->where('is_active', true)->latest('updated_at')->first();
    }

    public function all(): Collection
    {
        return $this->query()->orderByDesc('is_preset')->orderBy('id')->get();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return $this->query()->where('slug', $slug)->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))->exists();
    }

    public function create(array $attributes): ColourTheme
    {
        return $this->query()->create($attributes);
    }

    public function update(ColourTheme $theme, array $attributes): ColourTheme
    {
        $theme->fill($attributes)->save();

        return $theme;
    }

    public function delete(ColourTheme $theme): bool
    {
        return (bool) $theme->delete();
    }

    public function activate(ColourTheme $theme): ColourTheme
    {
        DB::transaction(function () use ($theme): void {
            $this->query()->whereKeyNot($theme->getKey())->where('is_active', true)->update(['is_active' => false]);
            $theme->forceFill(['is_active' => true])->save();
        });

        return $theme;
    }

    /**
     * @return Builder<ColourTheme>
     */
    private function query(): Builder
    {
        return $this->model->newQuery();
    }
}
