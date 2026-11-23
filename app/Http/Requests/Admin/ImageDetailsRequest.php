<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use Illuminate\Validation\Rules\Enum;

/**
 * Details of an existing image. Alt text, caption, place and year are
 * optional (owner's decision, 23 November 2026), unless a caller passes
 * $required = true to rulesFor().
 */
class ImageDetailsRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor(false);
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
        ] + self::translationRules();
    }

    /**
     * The Arabic alt text, caption and place: always optional (left empty,
     * Arabic pages show the English), with the English limits.
     *
     * @return array<string, list<string>>
     */
    public static function translationRules(): array
    {
        $rules = [];
        $limits = [MediaProperties::ALT_TEXT => 255, MediaProperties::CAPTION => 500, MediaProperties::PLACE => 120];

        foreach (Locales::prefixed() as $locale) {
            foreach ($limits as $property => $max) {
                $rules[MediaProperties::translationPath($locale, $property)] = ['nullable', 'string', 'max:'.$max];
            }
        }

        return $rules;
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
            MediaProperties::ALT_TEXT.'.required' => __('Describe the image for people who cannot see it.'),
            MediaProperties::CAPTION.'.required' => __('Add a caption before publishing.'),
            MediaProperties::PLACE.'.required' => __('Add the place before publishing.'),
            MediaProperties::YEAR.'.required' => __('Add the year before publishing.'),
            MediaProperties::TREATMENT.'.Illuminate\Validation\Rules\Enum' => __('Choose the portrait or archive treatment.'),
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
        $attributes = [
            MediaProperties::ALT_TEXT => __('alt text'),
            MediaProperties::PERMISSIONS_NOTE => __('permissions note'),
        ];

        foreach (Locales::prefixed() as $locale) {
            foreach ([MediaProperties::ALT_TEXT => __('alt text'), MediaProperties::CAPTION => __('caption'), MediaProperties::PLACE => __('place')] as $property => $label) {
                $attributes[MediaProperties::translationPath($locale, $property)] = __(':field (Arabic)', ['field' => $label]);
            }
        }

        return $attributes;
    }
}
