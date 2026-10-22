<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Permissions;
use App\Http\Requests\StoreTributeRequest;
use App\Models\Tribute;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Moderators fixing typos in a visitor's tribute.
 */
class TributeEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MODERATE_TRIBUTES);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.StoreTributeRequest::NAME_MAX_LENGTH],
            'location' => ['nullable', 'string', 'max:'.StoreTributeRequest::LOCATION_MAX_LENGTH],
            'message' => ['required', 'string', 'max:'.Tribute::MESSAGE_MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the name to show on the tribute.',
            'message.required' => 'Write the tribute text.',
            'message.max' => 'Shorten the tribute to :max characters or fewer.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['message' => 'tribute'];
    }
}
