<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\Search;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
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
            Search::PARAM => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The raw query, or an empty string.
     */
    public function term(): string
    {
        return (string) $this->validated(Search::PARAM, '');
    }
}
