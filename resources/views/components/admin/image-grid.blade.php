{{--
    Images of a record with their brand details: thumbnail, "needs details" flag, the
    details form (saveImage), optional cover/move controls (album), replace (a new file
    in place of the old one, keeping its details and order: replacements.{id}) and
    remove with confirmation. Expects the ManagesImages view data ($media, $missing, $images,
    $detailsRequired, $imageAccept, $imageHint).
--}}
@props(['media', 'missing', 'images', 'detailsRequired' => false, 'accept', 'hint', 'multiple' => false, 'label' => null, 'coverId' => null, 'orderable' => false, 'confirmingId' => null, 'canUpload' => true, 'archive' => null, 'decades' => []])
<section aria-labelledby="images-heading" data-image-grid>
    <div class="flex items-center justify-between gap-4">
        <h2 id="images-heading" class="font-display text-3xl font-light">{{ $label ?? __('Images') }}</h2>
        <hr class="rule-brass flex-1">
    </div>

    @if ($canUpload && ($multiple || $media->isEmpty()))
        <div class="mt-6">
            <x-form.upload model="newImages" id="new-images" :label="$multiple ? __('Add images') : __('Upload an image')"
                           :hint="__(':formats. Every image needs alt text, a caption, a place and a year before it can be published.', ['formats' => $hint])" :accept="$accept" :multiple="$multiple" image />
        </div>
    @elseif (! $canUpload)
        <p class="mt-6 text-sm font-normal text-ink-muted">{{ __('Save first, then add images.') }}</p>
    @endif

    @if ($media->isEmpty())
        <x-empty-state :message="__('No images yet.')" class="py-8" />
    @else
        <ul class="mt-8 space-y-6" @if ($orderable) x-data="sortableRows" @endif>
            @foreach ($media as $id => $image)
                @php $needs = $missing[$id] ?? []; @endphp
                <li wire:key="image-{{ $id }}" data-id="{{ $id }}" class="grid gap-6 rounded-card border border-rule bg-surface-panel p-5 lg:grid-cols-[14rem_1fr]"
                    @if ($orderable) draggable="true" x-on:dragstart="start($event, {{ $id }})" x-on:dragover.prevent="over($event)" x-on:dragend="end($event)" @endif>
                    <div>
                        <img src="{{ $image->getUrl() }}?v={{ $image->updated_at?->timestamp }}" alt="{{ $image->getCustomProperty(\App\Constants\MediaProperties::ALT_TEXT) ?: '' }}" class="photo-grid__thumb" loading="lazy" width="{{ $image->getCustomProperty(\App\Constants\MediaProperties::WIDTH) }}" height="{{ $image->getCustomProperty(\App\Constants\MediaProperties::HEIGHT) }}">
                        <p class="mt-2 truncate font-mono text-sm text-ink-muted" title="{{ $image->file_name }}">{{ $image->file_name }} · {{ \App\Support\FileSize::format((int) $image->size) }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($needs === [])
                                <x-admin.status-badge tone="cypress" :label="__('Ready')" />
                            @else
                                <x-admin.status-badge tone="bronze" :label="__('Needs details')" />
                            @endif
                            @if ($coverId !== null && (int) $coverId === (int) $id)
                                <x-admin.status-badge tone="cypress" :label="__('Cover')" />
                            @endif
                            @if ($archive !== null && ! empty($archive[$id][\App\Constants\Archive::FEATURED]))
                                <x-admin.status-badge tone="cypress" :label="__('Featured')" />
                            @endif
                            @if ($image instanceof \App\Models\Media && $image->needsTranslation())
                                <x-admin.status-badge tone="bronze" :label="__('Arabic missing')" data-needs-arabic />
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            @if ($orderable)
                                <x-button variant="quiet" size="sm" type="button" wire:click="setCover({{ $id }})">{{ __('Use as cover') }}</x-button>
                                <span class="inline-flex items-center gap-1">
                                    <button type="button" wire:click="movePhotoUp({{ $id }})" @disabled($loop->first) class="inline-flex size-8 items-center justify-center rounded-full border border-rule hover:border-focus disabled:opacity-40"><span aria-hidden="true">↑</span><span class="sr-only">{{ __('Move photo up') }}</span></button>
                                    <button type="button" wire:click="movePhotoDown({{ $id }})" @disabled($loop->last) class="inline-flex size-8 items-center justify-center rounded-full border border-rule hover:border-focus disabled:opacity-40"><span aria-hidden="true">↓</span><span class="sr-only">{{ __('Move photo down') }}</span></button>
                                </span>
                            @endif
                            <span x-data class="inline-flex" data-replace-image>
                                <input type="file" class="sr-only" tabindex="-1" aria-hidden="true" accept="{{ $accept }}" x-ref="replace" wire:model="replacements.{{ $id }}" id="replace-image-{{ $id }}">
                                <x-button variant="quiet" size="sm" type="button" x-on:click="$refs.replace.click()" wire:loading.attr="disabled" wire:target="replacements.{{ $id }}" aria-describedby="replace-image-{{ $id }}-error"><x-icon name="upload" class="size-4" />{{ __('Replace image') }}</x-button>
                            </span>
                            <x-button variant="danger" size="sm" type="button" wire:click="confirmRemoveImage({{ $id }})" data-remove-image><x-icon name="trash" class="size-4" />{{ __('Remove') }}</x-button>
                        </div>
                        <p class="mt-2 text-sm font-normal text-ink-muted" wire:loading wire:target="replacements.{{ $id }}" role="status">{{ __('Replacing the image…') }}</p>
                        <p id="replace-image-{{ $id }}-error" class="form-message form-error" data-form-error>@error('replacements.'.$id)<x-icon name="warning" class="mt-1 size-4 shrink-0" /><span role="alert">{{ $message }}</span>@enderror</p>
                    </div>
                    <div class="space-y-8">
                        <form novalidate wire:submit="saveImage({{ $id }})" class="space-y-4">
                            <x-admin.image-details :prefix="'images.'.$id" :required="$detailsRequired" />
                            <div class="flex items-center gap-4">
                                <x-button type="submit" variant="quiet" size="sm">{{ __('Save details') }}</x-button>
                                <span class="text-sm font-normal text-ink-muted" wire:loading wire:target="saveImage({{ $id }})">{{ __('Saving…') }}</span>
                            </div>
                        </form>
                        @if ($archive !== null && array_key_exists($id, $archive))
                            <form novalidate wire:submit="savePhotoArchive({{ $id }})" class="form-grid border-t border-rule pt-6" data-photo-archive>
                                <p class="t-label text-accent-text">{{ __('In the archive') }}</p>
                                <div class="form-row">
                                    <x-form.select class="form-col--half" :model="'archive.'.$id.'.'.\App\Constants\Archive::DECADE" :label="__('Decade')" :options="$decades" :placeholder="__('From the year')" :hint="__('Through the decades files the photo under its year unless you choose another decade.')" />
                                    <x-form.toggle :model="'archive.'.$id.'.'.\App\Constants\Archive::FEATURED" :label="__('Featured on the wall')" :hint="__('Hangs one size larger in the album.')" class="form-col--half" />
                                    @if (array_key_exists(\App\Constants\MediaProperties::ON_TIMELINE, $archive[$id]))
                                        <x-form.toggle :model="'archive.'.$id.'.'.\App\Constants\MediaProperties::ON_TIMELINE" :label="__('On the home timeline')" :hint="__('Offers the photo to its decade in “A life in chapters” on the home page. Once any photo is offered, only offered photos are used.')" class="form-col--half" />
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    <x-button type="submit" variant="quiet" size="sm">{{ __('Save archive details') }}</x-button>
                                    <span class="text-sm font-normal text-ink-muted" wire:loading wire:target="savePhotoArchive({{ $id }})">{{ __('Saving…') }}</span>
                                </div>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-admin.confirm-dialog :show="$confirmingId !== null" danger :title="__('Remove this image permanently?')" action="removeImage" cancel="cancelRemoveImage" :confirm-label="__('Remove image')">
        {{ __('The file is deleted from the site.') }} {{ __(\App\Constants\ConfirmDialog::IRREVERSIBLE) }}
    </x-admin.confirm-dialog>
</section>
