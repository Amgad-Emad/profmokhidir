<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Archive;
use Illuminate\Validation\Rule;

/**
 * An album photo's place in the archive: the decade it is filed under
 * (empty: its year decides) and whether it hangs featured on the wall.
 */
class PhotoArchiveRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::archiveRules();
    }

    /**
     * Shared with VideoRequest, which files recordings the same way.
     *
     * @return array<string, list<mixed>>
     */
    public static function archiveRules(): array
    {
        return [
            Archive::DECADE => ['nullable', 'integer', Rule::in(Archive::decades())],
            Archive::FEATURED => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + self::archiveMessages();
    }

    /**
     * @return array<string, string>
     */
    public static function archiveMessages(): array
    {
        return [Archive::DECADE.'.in' => 'Choose one of the listed decades.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::archiveAttributes();
    }

    /**
     * @return array<string, string>
     */
    public static function archiveAttributes(): array
    {
        return [Archive::FEATURED => 'featured'];
    }
}
