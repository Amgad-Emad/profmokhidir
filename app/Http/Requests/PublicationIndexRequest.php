<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\Catalogue;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string filters on the publications catalogue: type, an inclusive year
 * range (from, to) and a topic. Unknown types and topics are ignored by the
 * service; malformed input sends the visitor back to the unfiltered catalogue.
 * The older single `year` parameter still works as a one-year range.
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
        $year = ['nullable', 'integer', 'min:'.Catalogue::YEAR_MIN, 'max:'.Catalogue::YEAR_MAX];

        return [
            Catalogue::FILTER_TYPE => ['nullable', 'string', 'max:50'],
            Catalogue::FILTER_FROM => $year,
            Catalogue::FILTER_TO => $year,
            'year' => $year,
            Catalogue::FILTER_TOPIC => ['nullable', 'string', 'max:'.Catalogue::TOPIC_MAX],
        ];
    }

    /**
     * @return array{type: string|null, from: int|null, to: int|null, topic: string|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();
        $year = filled($validated['year'] ?? null) ? (int) $validated['year'] : null;
        $from = filled($validated[Catalogue::FILTER_FROM] ?? null) ? (int) $validated[Catalogue::FILTER_FROM] : null;
        $to = filled($validated[Catalogue::FILTER_TO] ?? null) ? (int) $validated[Catalogue::FILTER_TO] : null;

        return [
            'type' => filled($validated[Catalogue::FILTER_TYPE] ?? null) ? (string) $validated[Catalogue::FILTER_TYPE] : null,
            'from' => $from ?? $year,
            'to' => $to ?? ($from === null ? $year : null),
            'topic' => filled($validated[Catalogue::FILTER_TOPIC] ?? null) ? trim((string) $validated[Catalogue::FILTER_TOPIC]) : null,
        ];
    }
}
