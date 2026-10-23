{{-- Every state of the shared control (App\Constants\FormStates), pinned with data-demo-state where a real interaction would be needed. `$suffix` keeps ids unique per specimen. --}}
@use('App\Constants\FormStates')
<div class="grid gap-x-8 gap-y-6 sm:grid-cols-2" data-form-states>
    @foreach (FormStates::all() as $state => $meta)
        @php
            $id = 'state-'.$state.'-'.$suffix;
            $control = match ($state) {
                FormStates::VALID => ['data-corrected' => true],
                FormStates::DISABLED => ['disabled' => true],
                FormStates::READONLY => ['readonly' => true],
                FormStates::LOADING => ['data-loading' => true, 'aria-busy' => 'true'],
                default => [],
            };
            $frame = in_array($state, [FormStates::HOVER, FormStates::FOCUS], true) ? ['data-demo-state' => $state] : [];
            $value = in_array($state, [FormStates::DEFAULT, FormStates::HOVER, FormStates::FOCUS], true) ? null : '1984';
        @endphp
        <x-form.year :name="$id" :id="$id" :label="$meta['label']" :hint="$meta['look']" :value="$value" :optional="false"
                     :error="$state === FormStates::INVALID ? \App\Constants\FormControls::yearMessage() : null"
                     :control="$control + ['data-state' => $state]" :frame="$frame" />
    @endforeach
</div>
