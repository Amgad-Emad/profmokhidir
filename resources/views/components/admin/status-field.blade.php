{{-- Draft/published toggle (a labelled radio pair, so the state is readable without colour). --}}
@props(['name' => 'form.status', 'options' => \App\Constants\ContentStatus::labels(), 'publicUrl' => null])
@php $id = str_replace(['.', '_'], '-', $name); @endphp
<fieldset {{ $attributes->only('class') }} data-status-field>
    <legend class="t-label text-accent-text">Status</legend>
    <div class="mt-3 inline-flex rounded-full border border-rule bg-surface-panel p-1" role="radiogroup">
        @foreach ($options as $value => $label)
            <label class="status-option cursor-pointer rounded-full px-5 py-2 text-sm font-medium transition-colors has-checked:bg-fill has-checked:text-fill-ink">
                <input type="radio" name="{{ $id }}" value="{{ $value }}" wire:model.live="{{ $name }}" class="sr-only">
                <span class="flex items-center gap-2"><x-icon :name="\App\Constants\StatusTone::forContent(\App\Constants\ContentStatus::from($value))->icon()" class="size-4" />{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @if ($publicUrl)
        <p class="mt-3 text-sm"><a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="link-meta" data-view-on-site>View on site<span class="sr-only"> (opens in a new tab)</span></a></p>
    @endif
    @error($name)
        <p class="mt-3 flex items-start gap-2 text-sm text-accent-text" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</fieldset>
