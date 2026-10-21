<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\TributeRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query-string filters on /tributes: the relationship (students, colleagues,
 * family, friends) and the page of the book.
 */
class TributeIndexRequest extends FormRequest
{
    public const string RELATIONSHIP = 'relationship';

    protected $redirectRoute = 'tributes';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            self::RELATIONSHIP => ['nullable', 'string', Rule::enum(TributeRelationship::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function relationship(): ?TributeRelationship
    {
        $value = $this->validated()[self::RELATIONSHIP] ?? null;

        return filled($value) ? TributeRelationship::from((string) $value) : null;
    }
}
