<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * The draft being previewed in this request, if any. Set by the EnablePreview
 * middleware from a valid signed link; read by the Publishable published()
 * scope, which then lets that one record through alongside published ones.
 * Bound as a scoped singleton, so it never outlives the request.
 */
class PreviewContext
{
    /** @var class-string<Model>|null */
    private ?string $modelClass = null;

    private ?int $id = null;

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function activate(string $modelClass, int $id): void
    {
        $this->modelClass = $modelClass;
        $this->id = $id;
    }

    public function clear(): void
    {
        $this->modelClass = null;
        $this->id = null;
    }

    public function active(): bool
    {
        return $this->modelClass !== null;
    }

    /**
     * The previewed id when the given model is the previewed type.
     */
    public function idFor(Model $model): ?int
    {
        return $this->modelClass !== null && $model instanceof $this->modelClass ? $this->id : null;
    }
}
