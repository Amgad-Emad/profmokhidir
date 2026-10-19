<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class OtherItemRequest extends ContentRequest
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
            'slug' => self::slugRule('other_items', $this->ignoreId),
            'item_type' => ['nullable', 'string', 'max:50'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string', 'max:20000'],
            'date' => self::dateRule(),
            'category' => ['nullable', 'string', 'max:100'],
            'destination_url' => self::urlRule(),
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['item_type' => 'type', 'destination_url' => 'destination link'];
    }
}
