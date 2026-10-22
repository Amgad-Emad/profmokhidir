<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\ContentStatus;
use App\Constants\FormControls;
use App\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Shared pieces of every admin content form. The Livewire forms read rules(),
 * messages() and attributes() from these requests so validation lives in one
 * place whether a form is submitted through Livewire or a plain POST.
 */
abstract class ContentRequest extends FormRequest
{
    public const int EARLIEST_YEAR = FormControls::YEAR_MIN;

    public const int SLUG_MAX_LENGTH = 255;

    public const int URL_MAX_LENGTH = 2048;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MANAGE_CONTENT);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Choose either draft or published.',
            'slug.regex' => 'Use lowercase letters, numbers and hyphens only.',
            '*.url' => 'Enter a full web address starting with https://.',
        ];
    }

    /**
     * @return list<mixed>
     */
    public static function statusRule(): array
    {
        return ['required', new Enum(ContentStatus::class)];
    }

    /**
     * @return list<mixed>
     */
    public static function slugRule(string $table, ?int $ignoreId = null): array
    {
        return [
            'nullable', 'string', 'max:'.self::SLUG_MAX_LENGTH, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique($table, 'slug')->ignore($ignoreId)->withoutTrashed(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function urlRule(): array
    {
        return ['nullable', 'string', 'url:http,https', 'max:'.self::URL_MAX_LENGTH];
    }

    /**
     * @return list<string>
     */
    public static function yearRule(): array
    {
        return ['nullable', 'integer', 'between:'.self::EARLIEST_YEAR.','.FormControls::yearMax()];
    }

    /**
     * @return list<string>
     */
    public static function dateRule(): array
    {
        return ['nullable', 'date'];
    }
}
