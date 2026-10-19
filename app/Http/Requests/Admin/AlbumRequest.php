<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class AlbumRequest extends ContentRequest
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
            'slug' => self::slugRule('albums', $this->ignoreId),
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => self::statusRule(),
        ];
    }
}
