{{--
    Upload field for a Livewire temporary file (`name` is the bound property) with the
    current attached file (`media`), its original name and size, a download link
    (`href`) and a remove action (`remove`, a Livewire method). `accept` and `hint`
    tell the editor which formats and size are allowed.
--}}
@props(['name', 'label', 'media' => null, 'href' => null, 'remove' => null, 'accept' => null, 'hint' => null])
@php
    $id = str_replace(['.', '_'], '-', $name);
    $errorId = $id.'-error';
@endphp
<div {{ $attributes->only('class') }} data-file-field>
    <p class="t-label text-accent-text">{{ $label }}</p>
    @if ($media)
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-card border border-rule bg-surface-panel px-4 py-3" data-current-file>
            <div class="min-w-0">
                <p class="truncate font-medium text-ink">{{ $media->file_name }}</p>
                <p class="font-mono text-xs text-ink-muted">{{ strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION)) }} · {{ \App\Support\FileSize::format((int) $media->size) }}</p>
            </div>
            <div class="flex items-center gap-4 text-sm">
                @if ($href)
                    <a href="{{ $href }}" target="_blank" rel="noopener" class="link-meta">Open<span class="sr-only"> {{ $media->file_name }} (opens in a new tab)</span></a>
                @endif
                @if ($remove)
                    <button type="button" wire:click="{{ $remove }}" class="text-danger-tone underline-offset-4 hover:underline">Remove<span class="sr-only"> {{ $media->file_name }}</span></button>
                @endif
            </div>
        </div>
    @endif
    <label for="{{ $id }}" class="mt-3 block text-sm font-light text-ink-muted">{{ $media ? 'Replace the file' : 'Choose a file' }}@if ($hint) · {{ $hint }}@endif</label>
    <input id="{{ $id }}" type="file" wire:model="{{ $name }}" @if ($accept) accept="{{ $accept }}" @endif
           aria-describedby="{{ $errors->has($name) ? $errorId : '' }}"
           class="mt-2 block w-full text-sm text-ink file:mr-4 file:rounded-full file:border file:border-ink file:bg-surface-field file:px-5 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-surface-sunken">
    <p class="mt-2 text-sm font-light text-ink-muted" wire:loading wire:target="{{ $name }}">Uploading…</p>
    @error($name)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-2 text-sm text-danger-tone" role="alert"><x-icon name="warning" class="size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
