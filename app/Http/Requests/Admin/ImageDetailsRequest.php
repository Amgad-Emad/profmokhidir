<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use Illuminate\Validation\Rules\Enum;

/**
 * Details of an existing image. Alt text, caption, place and year are
 * optional on a draft and required once the image is going public.
 */
class ImageDetailsRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor((bool) $this->boolean('complete'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            MediaProperties::ALT_TEXT => [$presence, 'string', 'max:255'],
            MediaProperties::CAPTION => [$presence, 'string', 'max:500'],
            MediaProperties::PLACE => [$presence, 'string', 'max:120'],
            MediaProperties::YEAR => [$presence, ...array_slice(self::yearRule(), 1)],
            MediaProperties::TREATMENT => ['required', new Enum(MediaTreatment::class)],
            MediaProperties::CREDIT => ['nullable', 'string', 'max:255'],
            MediaProperties::PERMISSIONS_NOTE => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + self::detailMessages();
    }

    /**
     * @return array<string, string>
     */
    public static function detailMessages(): array
    {
        return [
            MediaProperties::ALT_TEXT.'.required' => 'Describe the image for people who cannot see it.',
            MediaProperties::CAPTION.'.required' => 'Add a caption before publishing.',
            MediaProperties::PLACE.'.required' => 'Add the place before publishing.',
            MediaProperties::YEAR.'.required' => 'Add the year before publishing.',
            MediaProperties::TREATMENT.'.Illuminate\Validation\Rules\Enum' => 'Choose the portrait or archive treatment.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::detailAttributes();
    }

    /**
     * @return array<string, string>
     */
    public static function detailAttributes(): array
    {
        return [
            MediaProperties::ALT_TEXT => 'alt text',
            MediaProperties::PERMISSIONS_NOTE => 'permissions note',
        ];
    }
}
