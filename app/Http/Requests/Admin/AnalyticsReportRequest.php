<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Analytics;
use App\Constants\AnalyticsRange;
use App\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * The analytics report's filters (query string): range, the first and last
 * day of a custom range, and the section of the pages table.
 */
class AnalyticsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::VIEW_ANALYTICS);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $earliest = Carbon::now()->subDays(Analytics::MAX_RANGE_DAYS)->toDateString();
        $custom = Rule::requiredIf(fn (): bool => $this->input('range') === AnalyticsRange::Custom->value);

        return [
            'range' => ['nullable', 'string', Rule::in(AnalyticsRange::values())],
            'from' => [$custom, 'nullable', 'date_format:Y-m-d', 'after_or_equal:'.$earliest, 'before_or_equal:today'],
            'to' => [$custom, 'nullable', 'date_format:Y-m-d', 'after_or_equal:'.$earliest, 'before_or_equal:today'],
            'section' => ['nullable', 'string', Rule::in(array_keys(Analytics::sections()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['from' => __('first day'), 'to' => __('last day')];
    }

    public function range(): AnalyticsRange
    {
        return AnalyticsRange::tryFrom((string) $this->validated('range')) ?? AnalyticsRange::DEFAULT;
    }

    public function section(): ?string
    {
        $section = $this->validated('section');

        return is_string($section) && $section !== '' ? $section : null;
    }
}
