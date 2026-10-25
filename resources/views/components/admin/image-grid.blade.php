{{--
    Images of a record with their brand details: thumbnail, "needs details" flag, the
    details form (saveImage), optional cover/move controls (album) and remove with
    confirmation. Expects the ManagesImages view data ($media, $missing, $images,
    $detailsRequired, $imageAccept, $imageHint).
--}}
@props(['media', 'missing', 'images', 'detailsRequired' => false, 'accept', 'hint', 'multiple' => false, 'label' => 'Images', 'coverId' => null, 'orderable' => false, 'confirmingId' => null, 'canUpload' => true, 'archive' => null, 'decades' => []])
<section aria-labelledby="images-heading" data-image-grid>
    <div class="flex items-center justify-between gap-4">
        <h2 id="images-heading" class="font-display text-3xl font-light">{{ $label }}</h2>
        <hr class="rule-brass flex-1">
    </div>

    @if ($canUpload)
        <div class="mt-6">
            <x-form.upload model="newImages" id="new-images" :label="$multiple ? 'Add images' : ($media->isEmpty() ? 'Upload an image' : 'Replace the image')"
                           :hint="$hint.'. Every image needs alt text, a caption, a place and a year before it can be published.'" :accept="$accept" :multiple="$multiple" image />
        </div>
    @else
        <p class="mt-6 text-sm font-normal text-ink-muted">Save first, then add images.</p>
    @endif

    @if ($media->isEmpty())
        <x-empty-state message="No images yet." class="py-8" />
    @else
        <ul class="mt-8 space-y-6" @if ($orderable) x-data="sortableRows" @endif>
            @foreach ($media as $id => $image)
                @php $needs = $missing[$id] ?? []; @endphp
                <li wire:key="image-{{ $id }}" data-id="{{ $id }}" class="grid gap-6 rounded-card border border-rule bg-surface-panel p-5 lg:grid-cols-[14rem_1fr]"
                    @if ($orderable) draggable="true" x-on:dragstart="start($event, {{ $id }})" x-on:dragover.prevent="over($event)" x-on:dragend="end($event)" @endif>
                    <div>
                        <img src="{{ $image->getUrl() }}" alt="{{ $image->getCustomProperty(\App\Constants\MediaProperties::ALT_TEXT) ?: '' }}" class="photo-grid__thumb" loading="lazy" width="{{ $image->getCustomProperty(\App\Constants\MediaProperties::WIDTH) }}" height="{{ $image->getCustomProperty(\App\Constants\MediaProperties::HEIGHT) }}">
                        <p class="mt-2 truncate font-mono text-sm text-ink-muted" title="{{ $image->file_name }}">{{ $image->file_name }} · {{ \App\Support\FileSize::format((int) $image->size) }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($needs === [])
                                <x-admin.status-badge tone="cypress" label="Ready" />
                            @else
                                <x-admin.status-badge tone="bronze" label="Needs details" />
                            @endif
                            @if ($coverId !== null && (int) $coverId === (int) $id)
                                <x-admin.status-badge tone="cypress" label="Cover" />
                            @endif
                            @if ($archive !== null && ! empty($archive[$id][\App\Constants\Archive::FEATURED]))
                                <x-admin.status-badge tone="cypress" label="Featured" />
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            @if ($orderable)
                                <x-button variant="quiet" size="sm" type="button" wire:click="setCover({{ $id }})">Use as cover</x-button>
                                <span class="inline-flex items-center gap-1">
                                    <button type="button" wire:click="movePhotoUp({{ $id }})" @disabled($loop->first) class="inline-flex size-8 items-center justify-center rounded-full border border-rule hover:border-focus disabled:opacity-40"><span aria-hidden="true">↑</span><span class="sr-only">Move photo up</span></button>
                                    <button type="button" wire:click="movePhotoDown({{ $id }})" @disabled($loop->last) class="inline-flex size-8 items-center justify-center rounded-full border border-rule hover:border-focus disabled:opacity-40"><span aria-hidden="true">↓</span><span class="sr-only">Move photo down</span></button>
                                </span>
                            @endif
                            <x-button variant="danger" size="sm" type="button" wire:click="confirmRemoveImage({{ $id }})" data-remove-image><x-icon name="trash" class="size-4" />Remove</x-button>
                        </div>
                    </div>
                    <div class="space-y-8">
                        <form novalidate wire:submit="saveImage({{ $id }})" class="space-y-4">
                            <x-admin.image-details :prefix="'images.'.$id" :required="$detailsRequired" />
                            <div class="flex items-center gap-4">
                                <x-button type="submit" variant="quiet" size="sm">Save details</x-button>
                                <span class="text-sm font-normal text-ink-muted" wire:loading wire:target="saveImage({{ $id }})">Saving…</span>
                            </div>
                        </form>
                        @if ($archive !== null && array_key_exists($id, $archive))
                            <form novalidate wire:submit="savePhotoArchive({{ $id }})" class="form-grid border-t border-rule pt-6" data-photo-archive>
                                <p class="t-label text-accent-text">In the archive</p>
                                <div class="form-row">
                                    <x-form.select class="form-col--half" :model="'archive.'.$id.'.'.\App\Constants\Archive::DECADE" label="Decade" :options="$decades" placeholder="From the year" hint="Through the decades files the photo under its year unless you choose another decade." />
                                    <x-form.toggle :model="'archive.'.$id.'.'.\App\Constants\Archive::FEATURED" label="Featured on the wall" hint="Hangs one size larger in the album." class="form-col--half" />
                                </div>
                                <div class="flex items-center gap-4">
                                    <x-button type="submit" variant="quiet" size="sm">Save archive details</x-button>
                                    <span class="text-sm font-normal text-ink-muted" wire:loading wire:target="savePhotoArchive({{ $id }})">Saving…</span>
                                </div>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-admin.confirm-dialog :show="$confirmingId !== null" danger title="Remove this image permanently?" action="removeImage" cancel="cancelRemoveImage" confirm-label="Remove image">
        The file is deleted from the site. {{ \App\Constants\ConfirmDialog::IRREVERSIBLE }}
    </x-admin.confirm-dialog>
</section>
