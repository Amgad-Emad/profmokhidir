<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The command palette's query: `q`, up to 100 characters. Content search is
 * for staff who manage content.
 */
class AdminSearchRequest extends FormRequest
{
    public const string FIELD = 'q';

    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MANAGE_CONTENT);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [self::FIELD => ['nullable', 'string', 'max:100']];
    }

    public function term(): string
    {
        return trim((string) $this->validated(self::FIELD, ''));
    }
}
