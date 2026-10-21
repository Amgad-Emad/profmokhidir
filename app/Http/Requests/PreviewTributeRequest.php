<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * The first step of the tribute flow: the same fields as the submission, checked
 * before the preview is shown. Consent is asked for on the preview step, so it
 * is not required here. Errors send the visitor back to the form.
 */
class PreviewTributeRequest extends StoreTributeRequest
{
    protected $redirectRoute = 'tributes.create';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['consent']);

        return $rules;
    }
}
