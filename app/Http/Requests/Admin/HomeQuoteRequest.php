<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\HomeTimeline;

class HomeQuoteRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'quote' => ['required', 'string', 'max:'.HomeTimeline::QUOTE_MAX_LENGTH],
            'attribution' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'quote.max' => 'Keep the quotation short: :max characters at most, so it reads as an interlude.',
        ];
    }
}
