<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Permissions;
use App\Constants\TermVocabulary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Arabic names of grouping terms: one row per term in use, each with an
 * optional name (blank leaves the English showing on Arabic pages).
 */
class TermTranslationRequest extends FormRequest
{
    public const int LABEL_MAX = 255;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MANAGE_CONTENT);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'rows' => ['array'],
            'rows.*.vocabulary' => ['required', Rule::enum(TermVocabulary::class)],
            'rows.*.term' => ['required', 'string', 'max:255'],
            'rows.*.label' => ['nullable', 'string', 'max:'.self::LABEL_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['rows.*.label' => __('Arabic name')];
    }
}
