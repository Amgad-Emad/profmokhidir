<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Uploads;

/**
 * A publication or teaching file: PDF, DOCX, PPTX or ZIP up to 50 MB.
 */
class DocumentUploadRequest extends ContentRequest
{
    public const string FIELD = 'document';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [self::FIELD => Uploads::documentRules()];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            self::FIELD.'.required' => __('Choose a file to upload.'),
            self::FIELD.'.mimes' => __('Use a PDF, Word, PowerPoint or ZIP file.'),
            self::FIELD.'.max' => __('Files can be at most :size.', ['size' => Uploads::documentMaxLabel()]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [self::FIELD => __('file')];
    }
}
