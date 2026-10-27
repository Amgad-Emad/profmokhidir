<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\VideoProvider;
use Illuminate\Validation\Rules\Enum;

class VideoRequest extends ContentRequest
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => self::slugRule('videos', $this->ignoreId),
            'date' => self::dateRule(),
            ...PhotoArchiveRequest::archiveRules(),
            'event' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'provider' => ['required', new Enum(VideoProvider::class)],
            'media_url' => ['required_unless:provider,'.VideoProvider::File->value, ...self::urlRule()],
            'captions_url' => self::urlRule(),
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'provider.Illuminate\Validation\Rules\Enum' => __('Choose where the video is hosted.'),
            'media_url.required_unless' => __('Enter the video link, or choose "Uploaded file" and upload it.'),
        ] + PhotoArchiveRequest::archiveMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['media_url' => __('video link'), 'captions_url' => __('captions link')] + PhotoArchiveRequest::archiveAttributes();
    }
}
