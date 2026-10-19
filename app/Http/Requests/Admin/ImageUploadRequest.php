<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Uploads;

/**
 * A new image: the file plus its initial details.
 */
class ImageUploadRequest extends ContentRequest
{
    public const string FIELD = 'image';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [self::FIELD => Uploads::imageRules()] + ImageDetailsRequest::rulesFor(false);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            self::FIELD.'.required' => 'Choose an image to upload.',
            self::FIELD.'.image' => 'The file must be an image.',
            self::FIELD.'.mimes' => 'Use a JPEG, PNG or WebP image.',
            self::FIELD.'.max' => 'Images can be at most '.Uploads::imageMaxLabel().'.',
        ] + ImageDetailsRequest::detailMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ImageDetailsRequest::detailAttributes();
    }
}
