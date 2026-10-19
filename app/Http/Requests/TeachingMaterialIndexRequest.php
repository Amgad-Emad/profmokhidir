<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string filters on /teaching.
 */
class TeachingMaterialIndexRequest extends FormRequest
{
    protected $redirectRoute = 'teaching';

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
            'topic' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array{topic: string|null, type: string|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'topic' => filled($validated['topic'] ?? null) ? trim((string) $validated['topic']) : null,
            'type' => filled($validated['type'] ?? null) ? (string) $validated['type'] : null,
        ];
    }
}
