@use('App\Constants\MediaProperties')
@use('App\Livewire\Admin\Media\Library')
<div>
    <x-admin.page-header :eyebrow="__('Media')" :title="__('Media library')" :intro="__('Every photograph on the site. Each needs alt text (a sentence describing it for people who cannot see it), a caption, a place and a year before its page can be published.')" />
    <x-admin.flash />

    {{-- Upload --}}
    <section id="upload" class="mt-10 rounded-card border border-rule bg-surface-panel p-5 sm:p-6" aria-labelledby="upload-heading" data-upload>
        <h2 id="upload-heading" class="font-display text-2xl font-light">{{ __('Upload photos') }}</h2>
        <p class="mt-1 text-sm font-normal text-ink-muted">{{ __('Choose the album first, then drop up to 20 photos at once. They upload one batch at a time; the line shows how far it has got.') }}</p>
        <div class="form-row mt-5">
            <x-form.combobox class="form-col--third" model="uploadAlbumId" :label="__('Into album')" :options="$albums" :placeholder="__('Type to find an album')" required />
            <x-form.upload class="form-col--two-thirds" model="uploads" :label="__('Photos')" :hint="$uploadHint" :accept="$uploadAccept" multiple image />
        </div>
    </section>

    {{-- Show, search and filters --}}
    <div class="list-tools mt-10">
        <div class="filter-chips" role="group" aria-label="{{ __('Show') }}" data-filter="show">
            <span class="filter-chips__label t-label" aria-hidden="true">{{ __('Show') }}</span>
            @foreach ($showOptions as $value => $label)
                <button type="button" class="filter-chip" wire:click="show('{{ $value }}')" aria-pressed="{{ $this->showing() === $value ? 'true' : 'false' }}" data-show="{{ $value === '' ? 'all' : $value }}">
                    {{ $label }}
                    @if ($value === Library::SHOW_MISSING_ALT && $missingAltCount > 0)<span class="admin-count admin-count--quiet">@num($missingAltCount)</span>@endif
                    @if ($value === Library::SHOW_INCOMPLETE && $incompleteCount > 0)<span class="admin-count admin-count--quiet">@num($incompleteCount)</span>@endif
                </button>
            @endforeach
        </div>
        <form novalidate class="form-row" wire:submit.prevent role="search" aria-label="{{ __('Search images') }}">
            <x-form.input type="search" model="search" id="search" live :label="__('Search')" :optional="false" loading="search" class="form-col--half" :placeholder="__('File name, alt text, caption or place')" autocomplete="off" data-shortcut="search" />
            <x-form.select model="album" id="filter-album" class="form-col--quarter" live :label="__('Album')" :optional="false" :placeholder="__('All albums')" :options="$albums" data-filter="album" />
            <x-form.select model="collection" id="filter-collection" class="form-col--quarter" live :label="__('Used as')" :optional="false" :placeholder="__('Anything')" :options="$collections" />
        </form>
    </div>

    @if ($images->isEmpty())
        <x-admin.list-empty class="mt-8" :title="__('No photographs yet')" :text="__('Photographs uploaded here are filed in albums for the Pictures page. Portraits and video posters appear here too.')" :filtered="$filtered" />
    @else
        <ul class="library-grid mt-8" data-library>
            @foreach ($images as $image)
                @php
                    $id = (int) $image->getKey();
                    $usage = $usages[$id];
                    $needs = $missing[$id];
                    $alt = $image->getTranslatedProperty(MediaProperties::ALT_TEXT, \App\Constants\Locales::DEFAULT);
                    $caption = $image->getCustomProperty(MediaProperties::CAPTION) ?: $image->file_name;
                @endphp
                <li wire:key="media-{{ $id }}" class="library-tile" data-media="{{ $id }}">
                    <button type="button" class="library-tile__open" wire:click="edit({{ $id }})" aria-label="{{ __('Details of :image', ['image' => $caption]) }}">
                        <img src="{{ $image->getUrl() }}" alt="" loading="lazy" decoding="async">
                    </button>
                    <div class="library-tile__body">
                        <p class="library-tile__name" title="{{ $caption }}" dir="auto">{{ $caption }}</p>
                        <p class="library-tile__meta" dir="auto">{{ $usage['type_label'] }} · {{ $usage['label'] }}{{ $usage['trashed'] ? ' '.__('(in trash)') : '' }}</p>
                        @if (blank($alt))
                            <p class="library-tile__flag"><x-icon name="warning" class="size-3.5" />{{ __('Needs alt text') }}</p>
                        @elseif ($needs !== [])
                            <p class="library-tile__flag"><x-icon name="clock" class="size-3.5" />{{ __('Needs details') }}</p>
                        @else
                            <p class="library-tile__flag library-tile__flag--ready"><x-icon name="check" class="size-3.5" />{{ __('Ready') }}</p>
                        @endif
                        @if ($image->needsTranslation())
                            <p class="library-tile__flag" data-needs-arabic><x-icon name="info" class="size-3.5" />{{ __('Arabic missing') }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $images->links('components.admin.pagination') }}
    @endif

    {{-- Details drawer --}}
    <x-admin.drawer :show="$editing !== null" :title="__('Image details')" close="cancelEdit" id="details" :description="$editing ? $editing->file_name.' · '.\App\Support\FileSize::format((int) $editing->size) : null">
        @if ($editing)
            @php $editingUsage = $usages[(int) $editing->getKey()] ?? app(\App\Services\MediaService::class)->usage($editing); @endphp
            <div data-details-dialog>
                <img src="{{ $editing->getUrl() }}" alt="" class="w-full rounded-card border border-rule object-contain" style="max-height: 16rem">
                <p class="mt-3 text-sm font-normal text-ink-muted">
                    {{ $editingUsage['trashed'] ? __('Used by :type: :label (in the trash).', ['type' => $editingUsage['type_label'], 'label' => $editingUsage['label']]) : __('Used by :type: :label.', ['type' => $editingUsage['type_label'], 'label' => $editingUsage['label']]) }}
                    @if ($editingUsage['url'])<a href="{{ $editingUsage['url'] }}" class="link-meta">{{ __('Open it') }}</a>@endif
                </p>
                @if ($editingRequired)
                    <x-alert tone="bronze" class="mt-4">{{ __('This image is on a published page, so every detail is required.') }}</x-alert>
                @endif
                <form wire:submit="saveDetails" class="mt-6 space-y-4" novalidate>
                    <x-admin.image-details prefix="details" :required="$editingRequired" />
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-rule pt-5">
                        <x-button variant="danger" size="sm" type="button" wire:click="confirm('delete', {{ (int) $editing->getKey() }})" data-delete><x-icon name="trash" class="size-4" />{{ __('Delete image') }}</x-button>
                        <div class="flex flex-wrap gap-3">
                            <x-button type="button" variant="quiet" wire:click="cancelEdit">{{ __('Cancel') }}</x-button>
                            <x-button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('Save details') }}</x-button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </x-admin.drawer>

    <x-admin.confirm-dialog :show="$confirming !== null" danger :title="__('Delete this image permanently?')" action="delete" :confirm-label="__('Delete permanently')">
        @if ($confirming)
            @php
                $owner = '<strong class="font-medium">'.e($confirmingUsage['type_label'].': '.$confirmingUsage['label']).'</strong>'.($confirmingUsage['trashed'] ? ' '.e(__('(in the trash)')) : '');
                $file = '<strong class="font-medium">'.e($confirming->file_name).'</strong>';
                $sentence = match ($confirmingUsage['collection']) {
                    \App\Constants\MediaCollections::PORTRAIT => ':file is used by :owner as the portrait.',
                    \App\Constants\MediaCollections::THUMBNAIL => ':file is used by :owner as the poster image.',
                    default => ':file is used by :owner.',
                };
            @endphp
            <p data-usage>{!! strtr(e(__($sentence)), [':file' => $file, ':owner' => $owner]) !!}</p>
            <p class="mt-2">{{ __('The file is removed from that page at once.') }} {{ __(\App\Constants\ConfirmDialog::IRREVERSIBLE) }}</p>
        @endif
    </x-admin.confirm-dialog>
</div>
