{{-- Under a tribute's row: its words, or the typo form while it is being fixed (App\Livewire\Admin\Tributes\Moderate). --}}
@if ($editing)
    <form wire:submit="saveEdit" class="form-grid" novalidate data-edit-form>
        <div class="form-row">
            <x-form.input class="form-col--half" model="edit.name" :label="__('Name')" required />
            <x-form.input class="form-col--half" model="edit.location" :label="__('Location')" />
        </div>
        <x-form.textarea model="edit.message" :label="__('Tribute')" rows="5" required />
        <div class="flex flex-wrap gap-3">
            <x-button type="submit" size="sm">{{ __('Save changes') }}</x-button>
            <x-button type="button" variant="quiet" size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</x-button>
        </div>
    </form>
@else
    <blockquote dir="auto" class="whitespace-pre-line font-display text-xl font-light italic leading-relaxed text-ink" data-tribute-message>{{ $tribute->message }}</blockquote>
@endif
