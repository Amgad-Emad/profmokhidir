<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\Analytics;
use App\Constants\AnalyticsEventType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * One analytics beacon (sent by resources/js/analytics.js with sendBeacon):
 * t type, v the view's random id, sw the screen width (a signal that a real
 * browser ran the script); for a page view p path, ti title, r referrer and
 * u UTM source; for engagement e engaged seconds and s scroll depth. Anything
 * malformed is refused with an empty 422 and nothing is stored.
 */
class CollectAnalyticsRequest extends FormRequest
{
    /**
     * sendBeacon posts the JSON as text/plain (a CORS-safelisted type); read it from the body.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isJson() || $this->request->count() > 0) {
            return;
        }

        $decoded = json_decode((string) $this->getContent(), true);

        if (is_array($decoded)) {
            $this->merge($decoded);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $pageview = Rule::requiredIf(fn (): bool => $this->input('t') === AnalyticsEventType::Pageview->value);
        $engagement = Rule::requiredIf(fn (): bool => $this->input('t') === AnalyticsEventType::Engagement->value);

        return [
            't' => ['required', 'string', Rule::in(AnalyticsEventType::values())],
            'v' => ['required', 'string', 'uuid'],
            'sw' => ['required', 'integer', 'between:100,20000'],
            'p' => [$pageview, 'nullable', 'string', 'max:255', 'regex:#^/[^\s?\#]*$#'],
            'ti' => ['nullable', 'string', 'max:300'],
            'r' => ['nullable', 'string', 'max:2000'],
            'u' => ['nullable', 'string', 'max:200'],
            'e' => [$engagement, 'nullable', 'integer', 'min:0', 'max:86400'],
            's' => [$engagement, 'nullable', 'integer', Rule::in([0, ...Analytics::SCROLL_DEPTHS])],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->noContent(422));
    }
}
