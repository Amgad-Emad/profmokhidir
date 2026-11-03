<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Commentaries;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\CommentaryService;
use App\Support\DataTable\Column;
use Illuminate\Database\Eloquent\Model;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::COMMENTARY;
    }

    protected function service(): AbstractContentService
    {
        return app(CommentaryService::class);
    }

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::text('byline', __('Byline'), width: '10rem', visible: false),
            Column::date('date', __('Date')),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->byline, is_array($item->tags) ? implode(__(', '), $item->tags) : null];
    }
}
