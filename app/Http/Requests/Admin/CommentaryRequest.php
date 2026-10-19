<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class CommentaryRequest extends ContentRequest
{
    public const int MAX_TAGS = 10;

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
            'slug' => self::slugRule('commentaries', $this->ignoreId),
            'date' => self::dateRule(),
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string', 'max:100000'],
            'external_url' => self::urlRule(),
            'tags' => ['nullable', 'string', 'max:500'],
            'byline' => ['nullable', 'string', 'max:255'],
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['external_url' => 'external link'];
    }
}
