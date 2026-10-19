@use('App\Constants\MediaProperties')
<div>
    <x-admin.page-header eyebrow="Media" title="Media library" intro="Every image on the site. Each needs alt text, a caption, a place and a year before it can be published.">
        @if ($incompleteCount > 0)
            <x-admin.status-badge tone="bronze" :label="$incompleteCount.' '.\Illuminate\Support\Str::plural('image', $incompleteCount).' need details'" />
        @endif
    </x-admin.page-header>

    <div class="mt-8"><x-admin.flash /></div>

    {{-- Upload --}}
    <section class="mt-4 rounded-card border border-stone bg-ivory p-5" aria-labelledby="upload-heading" data-upload>
        <h2 id="upload-heading" class="font-display text-2xl font-light">Upload images</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,18rem)_1fr]">
            <x-admin.select-field name="uploadAlbumId" label="Into album" :options="$albums" placeholder="Choose an album" required live />
            <div>
                <label for="uploads" class="t-label block text-bronze">Images</label>
                <p class="mt-2 text-sm font-light text-ash">{{ $uploadHint }}</p>
                <input id="uploads" type="file" wire:model="uploads" accept="{{ $uploadAccept }}" multiple
                       class="mt-2 block w-full text-sm text-evergreen file:mr-4 file:rounded-full file:border file:border-evergreen file:bg-ivory file:px-5 file:py-2 file:text-sm file:font-medium file:text-evergreen hover:file:bg-linen">
                <p class="mt-2 text-sm font-light text-ash" wire:loading wire:target="uploads">Uploading…</p>
                @error('uploads') <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p> @enderror
                @error('uploads.*') <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Search and filters --}}
    <form class="mt-8 grid gap-4 md:grid-cols-4" wire:submit.prevent role="search" aria-label="Search images">
        <div class="md:col-span-2">
            <label for="search" class="t-label block text-bronze">Search</label>
            <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="File name, alt text, caption or place" class="mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen placeholder:text-ash focus:border-bronze">
        </div>
        <div>
            <label for="filter-collection" class="t-label block text-bronze">Used as</label>
            <select id="filter-collection" wire:model.live="collection" class="form-select mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen focus:border-bronze">
                <option value="">All</option>
                @foreach ($collections as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="filter-treatment" class="t-label block text-bronze">Treatment</label>
            <select id="filter-treatment" wire:model.live="treatment" class="form-select mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen focus:border-bronze">
                <option value="">All</option>
                @foreach ($treatments as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <label class="flex items-center gap-3 text-sm md:col-span-4">
            <input type="checkbox" wire:model.live="incomplete" class="size-4 rounded-sm border-stone text-evergreen">
            Only images that still need details
        </label>
    </form>

    @if ($images->isEmpty())
        <x-empty-state :message="trim($search) !== '' || $collection !== '' || $treatment !== '' || $incomplete ? 'No images match these filters.' : 'No images have been uploaded yet.'" />
    @else
        <ul class="photo-grid mt-8" data-library>
            @foreach ($images as $image)
                @php $id = (int) $image->getKey(); $usage = $usages[$id]; $needs = $missing[$id]; @endphp
                <li wire:key="media-{{ $id }}" class="flex flex-col gap-2 rounded-card border border-stone bg-ivory p-3" data-media="{{ $id }}">
                    <img src="{{ $image->getUrl() }}" alt="{{ $image->getCustomProperty(MediaProperties::ALT_TEXT) ?: '' }}" class="photo-grid__thumb" loading="lazy">
                    <p class="truncate text-sm font-medium text-evergreen" title="{{ $image->getCustomProperty(MediaProperties::CAPTION) ?: $image->file_name }}">{{ $image->getCustomProperty(MediaProperties::CAPTION) ?: $image->file_name }}</p>
                    <p class="text-xs font-light text-ash">{{ $usage['type_label'] }} · {{ $usage['label'] }}{{ $usage['trashed'] ? ' (in trash)' : '' }}</p>
                    <div class="flex flex-wrap gap-1">
                        <x-admin.status-badge :tone="$needs === [] ? 'cypress' : 'bronze'" :label="$needs === [] ? 'Ready' : 'Needs details'" class="px-2 py-0.5 text-[0.625rem]" />
                    </div>
                    <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-sm">
                        <button type="button" wire:click="edit({{ $id }})" class="link-meta">Details<span class="sr-only"> of {{ $image->file_name }}</span></button>
                        @if ($usage['url'])
                            <a href="{{ $usage['url'] }}" class="link-meta">Open {{ strtolower($usage['type_label']) }}</a>
                        @endif
                        <button type="button" wire:click="confirm('delete', {{ $id }})" class="text-bronze underline-offset-4 hover:underline" data-delete>Delete<span class="sr-only"> {{ $image->file_name }}</span></button>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $images->links('components.admin.pagination') }}
    @endif

    {{-- Details panel --}}
    @if ($editing)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-evergreen/70 p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelEdit()" data-details-dialog>
            <div role="dialog" aria-modal="true" aria-labelledby="details-title" class="max-h-full w-full max-w-2xl overflow-y-auto rounded-card border border-stone bg-ivory p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="details-title" class="font-display text-3xl font-light leading-tight">Image details</h2>
                        <p class="mt-1 font-mono text-xs text-ash">{{ $editing->file_name }} · {{ \App\Support\FileSize::format((int) $editing->size) }}</p>
                    </div>
                    <img src="{{ $editing->getUrl() }}" alt="" class="size-24 rounded-card border border-stone object-cover">
                </div>
                @if ($editingRequired)
                    <x-alert tone="bronze" class="mt-4">This image is on a published page, so every detail is required.</x-alert>
                @endif
                <form wire:submit="saveDetails" class="mt-6 space-y-4" novalidate>
                    <x-admin.image-details prefix="details" :required="$editingRequired" />
                    <div class="flex flex-wrap justify-end gap-3 pt-2">
                        <x-button type="button" variant="outline" wire:click="cancelEdit">Cancel</x-button>
                        <x-button type="submit" wire:loading.attr="disabled">Save details</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <x-admin.confirm-dialog :show="$confirming !== null" title="Delete this image?" action="delete" confirm-label="Delete image">
        @if ($confirming)
            @php
                $role = match ($confirmingUsage['collection']) {
                    \App\Constants\MediaCollections::PORTRAIT => ' as the portrait',
                    \App\Constants\MediaCollections::THUMBNAIL => ' as the poster image',
                    default => '',
                };
            @endphp
            <p data-usage><strong class="font-medium">{{ $confirming->file_name }}</strong> is used by <strong class="font-medium">{{ $confirmingUsage['type_label'] }}: {{ $confirmingUsage['label'] }}</strong>{{ $confirmingUsage['trashed'] ? ' (in the trash)' : '' }}{{ $role }}.</p>
            <p class="mt-2">The file is removed from that page at once and cannot be restored.</p>
        @endif
    </x-admin.confirm-dialog>
</div>
