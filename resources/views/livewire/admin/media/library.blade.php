@use('App\Constants\MediaProperties')
@use('App\Livewire\Admin\Media\Library')
<div>
    <x-admin.page-header eyebrow="Media" title="Media library" intro="Every photograph on the site. Each needs alt text (a sentence describing it for people who cannot see it), a caption, a place and a year before its page can be published." />
    <x-admin.flash />

    {{-- Upload --}}
    <section id="upload" class="mt-10 rounded-card border border-rule bg-surface-panel p-5 sm:p-6" aria-labelledby="upload-heading" data-upload>
        <h2 id="upload-heading" class="font-display text-2xl font-light">Upload photos</h2>
        <p class="mt-1 text-sm font-light text-ink-muted">Choose the album first, then drop up to 20 photos at once. They upload one batch at a time; the line shows how far it has got.</p>
        <div class="form-row mt-5">
            <x-form.combobox class="form-col--third" model="uploadAlbumId" label="Into album" :options="$albums" placeholder="Type to find an album" required />
            <x-form.upload class="form-col--two-thirds" model="uploads" label="Photos" :hint="$uploadHint" :accept="$uploadAccept" multiple image />
        </div>
    </section>

    {{-- Show, search and filters --}}
    <div class="list-tools mt-10">
        <div class="filter-chips" role="group" aria-label="Show" data-filter="show">
            <span class="filter-chips__label t-label" aria-hidden="true">Show</span>
            @foreach ($showOptions as $value => $label)
                <button type="button" class="filter-chip" wire:click="show('{{ $value }}')" aria-pressed="{{ $this->showing() === $value ? 'true' : 'false' }}" data-show="{{ $value === '' ? 'all' : $value }}">
                    {{ $label }}
                    @if ($value === Library::SHOW_MISSING_ALT && $missingAltCount > 0)<span class="admin-count admin-count--quiet">{{ $missingAltCount }}</span>@endif
                    @if ($value === Library::SHOW_INCOMPLETE && $incompleteCount > 0)<span class="admin-count admin-count--quiet">{{ $incompleteCount }}</span>@endif
                </button>
            @endforeach
        </div>
        <form novalidate class="form-row" wire:submit.prevent role="search" aria-label="Search images">
            <x-form.input type="search" model="search" id="search" live label="Search" :optional="false" loading="search" class="form-col--half" placeholder="File name, alt text, caption or place" autocomplete="off" data-shortcut="search" />
            <x-form.select model="album" id="filter-album" class="form-col--quarter" live label="Album" :optional="false" placeholder="All albums" :options="$albums" data-filter="album" />
            <x-form.select model="collection" id="filter-collection" class="form-col--quarter" live label="Used as" :optional="false" placeholder="Anything" :options="$collections" />
        </form>
    </div>

    @if ($images->isEmpty())
        <x-admin.list-empty class="mt-8" title="No photographs yet" text="Photographs uploaded here are filed in albums for the Pictures page. Portraits and video posters appear here too." :filtered="$filtered" />
    @else
        <ul class="library-grid mt-8" data-library>
            @foreach ($images as $image)
                @php
                    $id = (int) $image->getKey();
                    $usage = $usages[$id];
                    $needs = $missing[$id];
                    $alt = $image->getCustomProperty(MediaProperties::ALT_TEXT);
                    $caption = $image->getCustomProperty(MediaProperties::CAPTION) ?: $image->file_name;
                @endphp
                <li wire:key="media-{{ $id }}" class="library-tile" data-media="{{ $id }}">
                    <button type="button" class="library-tile__open" wire:click="edit({{ $id }})" aria-label="Details of {{ $caption }}">
                        <img src="{{ $image->getUrl() }}" alt="" loading="lazy" decoding="async">
                    </button>
                    <div class="library-tile__body">
                        <p class="library-tile__name" title="{{ $caption }}">{{ $caption }}</p>
                        <p class="library-tile__meta">{{ $usage['type_label'] }} · {{ $usage['label'] }}{{ $usage['trashed'] ? ' (in trash)' : '' }}</p>
                        @if (blank($alt))
                            <p class="library-tile__flag"><x-icon name="warning" class="size-3.5" />Needs alt text</p>
                        @elseif ($needs !== [])
                            <p class="library-tile__flag"><x-icon name="clock" class="size-3.5" />Needs details</p>
                        @else
                            <p class="library-tile__flag library-tile__flag--ready"><x-icon name="check" class="size-3.5" />Ready</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $images->links('components.admin.pagination') }}
    @endif

    {{-- Details drawer --}}
    <x-admin.drawer :show="$editing !== null" title="Image details" close="cancelEdit" id="details" :description="$editing ? $editing->file_name.' · '.\App\Support\FileSize::format((int) $editing->size) : null">
        @if ($editing)
            @php $editingUsage = $usages[(int) $editing->getKey()] ?? app(\App\Services\MediaService::class)->usage($editing); @endphp
            <div data-details-dialog>
                <img src="{{ $editing->getUrl() }}" alt="" class="w-full rounded-card border border-rule object-contain" style="max-height: 16rem">
                <p class="mt-3 text-sm font-light text-ink-muted">
                    Used by {{ $editingUsage['type_label'] }}: {{ $editingUsage['label'] }}{{ $editingUsage['trashed'] ? ' (in the trash)' : '' }}.
                    @if ($editingUsage['url'])<a href="{{ $editingUsage['url'] }}" class="link-meta">Open it</a>@endif
                </p>
                @if ($editingRequired)
                    <x-alert tone="bronze" class="mt-4">This image is on a published page, so every detail is required.</x-alert>
                @endif
                <form wire:submit="saveDetails" class="mt-6 space-y-4" novalidate>
                    <x-admin.image-details prefix="details" :required="$editingRequired" />
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-rule pt-5">
                        <x-button variant="danger" size="sm" type="button" wire:click="confirm('delete', {{ (int) $editing->getKey() }})" data-delete><x-icon name="trash" class="size-4" />Delete image</x-button>
                        <div class="flex flex-wrap gap-3">
                            <x-button type="button" variant="quiet" wire:click="cancelEdit">Cancel</x-button>
                            <x-button type="submit" variant="primary" wire:loading.attr="disabled">Save details</x-button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </x-admin.drawer>

    <x-admin.confirm-dialog :show="$confirming !== null" danger title="Delete this image permanently?" action="delete" confirm-label="Delete permanently">
        @if ($confirming)
            @php
                $role = match ($confirmingUsage['collection']) {
                    \App\Constants\MediaCollections::PORTRAIT => ' as the portrait',
                    \App\Constants\MediaCollections::THUMBNAIL => ' as the poster image',
                    default => '',
                };
            @endphp
            <p data-usage><strong class="font-medium">{{ $confirming->file_name }}</strong> is used by <strong class="font-medium">{{ $confirmingUsage['type_label'] }}: {{ $confirmingUsage['label'] }}</strong>{{ $confirmingUsage['trashed'] ? ' (in the trash)' : '' }}{{ $role }}.</p>
            <p class="mt-2">The file is removed from that page at once. {{ \App\Constants\ConfirmDialog::IRREVERSIBLE }}</p>
        @endif
    </x-admin.confirm-dialog>
</div>
