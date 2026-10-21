<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Catalogue;
use App\Constants\PublicationType;
use Illuminate\Validation\Rules\Enum;

class PublicationRequest extends ContentRequest
{
    public function __construct(private readonly ?int $ignoreId = null)
    {
        parent::__construct();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'slug' => self::slugRule('publications', $this->ignoreId),
            'type' => ['required', new Enum(PublicationType::class)],
            'authors' => ['nullable', 'string', 'max:1000'],
            'year' => self::yearRule(),
            'venue_publisher' => ['nullable', 'string', 'max:255'],
            'doi' => ['nullable', 'string', 'max:255'],
            'citation' => ['nullable', 'string', 'max:2000'],
            'abstract' => ['nullable', 'string', 'max:5000'],
            'url' => self::urlRule(),
            'topic' => ['nullable', 'string', 'max:'.Catalogue::TOPIC_MAX],
            'is_featured' => ['boolean'],
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'type.Illuminate\Validation\Rules\Enum' => 'Choose one of the publication types.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['venue_publisher' => 'venue or publisher', 'doi' => 'DOI', 'url' => 'link', 'is_featured' => 'selected works'];
    }
}
