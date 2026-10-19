<?php

declare(strict_types=1);

namespace App\Livewire\Admin\OtherItems;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaTreatment;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\OtherItemRequest;
use App\Livewire\Admin\Concerns\ManagesImages;
use App\Livewire\Admin\ContentForm;
use App\Models\OtherItem;
use App\Services\AbstractContentService;
use App\Services\OtherItemService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Form extends ContentForm
{
    use ManagesImages;

    public function mount(?OtherItem $otherItem = null): void
    {
        $this->initialise($otherItem);
        $this->loadImages();
    }

    protected function type(): string
    {
        return ContentTypes::OTHER_ITEM;
    }

    protected function service(): AbstractContentService
    {
        return app(OtherItemService::class);
    }

    protected function request(): ContentRequest
    {
        return new OtherItemRequest($this->recordId);
    }

    protected function defaults(): array
    {
        return [
            'title' => '',
            'slug' => null,
            'item_type' => null,
            'summary' => null,
            'body' => null,
            'date' => null,
            'category' => null,
            'destination_url' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.other-items.form';
    }

    protected function viewData(): array
    {
        return $this->imagesViewData();
    }

    protected function imageCollection(): string
    {
        return MediaCollections::MEDIA;
    }

    protected function defaultTreatment(): MediaTreatment
    {
        return MediaTreatment::Archive;
    }

    protected function attachImage(Model $record, UploadedFile $file, array $details): Media
    {
        return app(OtherItemService::class)->addMedia($record, $file, $details);
    }
}
