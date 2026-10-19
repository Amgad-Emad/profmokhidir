<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string tag filter on /commentary.
 */
class CommentaryIndexRequest extends FormRequest
{
    protected $redirectRoute = 'commentary';

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
            'tag' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function tag(): ?string
    {
        $tag = $this->validated()['tag'] ?? null;

        return filled($tag) ? trim((string) $tag) : null;
    }
}
