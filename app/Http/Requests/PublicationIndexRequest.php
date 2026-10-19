<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string filters on /publications. Unknown type values are ignored by the
 * service; malformed input sends the visitor back to the unfiltered list.
 */
class PublicationIndexRequest extends FormRequest
{
    protected $redirectRoute = 'publications';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:9999'],
        ];
    }

    /**
     * @return array{type: string|null, year: int|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'type' => filled($validated['type'] ?? null) ? (string) $validated['type'] : null,
            'year' => filled($validated['year'] ?? null) ? (int) $validated['year'] : null,
        ];
    }
}
