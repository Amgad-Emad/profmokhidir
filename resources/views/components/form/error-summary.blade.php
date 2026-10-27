{{--
    One calm note at the top of a long form that failed: the title, a line on what to do, and
    each message as a link to its field (ids follow App\Services\FormFieldService::idFor; pass
    `targets` to point a key elsewhere, e.g. ['code' => 'code-1'], and `except` for keys the
    page reports in its own words). After a plain POST it takes
    focus when the page loads so screen readers hear it first. In a Livewire form (`after-save`)
    it stays hidden until a save fails (forms.js dispatches form-invalid), then moves focus to
    the first field in error. `messages` (key => message) shows a fixed list (the style guide).
    Renders an empty, hidden shell otherwise, which forms.js fills
    when a plain form is sent with fields the browser can already see are wrong.
--}}
@props(['bag' => 'default', 'targets' => [], 'except' => [], 'title' => null, 'intro' => null, 'afterSave' => false, 'messages' => null])
@inject('forms', 'App\Services\FormFieldService')
@php
    $title ??= __(\App\Constants\FormControls::ERROR_SUMMARY_TITLE);
    $errorBag = $messages !== null ? new \Illuminate\Support\MessageBag($messages) : ($errors ?? new \Illuminate\Support\ViewErrorBag)->getBag($bag);
    $keys = array_values(array_diff($errorBag->keys(), $except));
    $hasErrors = $keys !== [];
@endphp
@php
    $state = match (true) {
        $afterSave => ' x-data="{ shown: false }" x-show="shown" x-cloak x-on:form-invalid.window="shown = true"',
        ! $hasErrors => ' hidden',
        $messages === null => ' x-data x-init="$el.focus()"',
        default => '',
    };
@endphp
<div {{ $attributes->class(['form-summary']) }} role="alert" tabindex="-1" data-error-summary{!! $state !!}>
    <x-icon name="warning" class="mt-0.5 size-5 shrink-0" />
    <div>
        <p class="form-summary__title">{{ $title }}</p>
        @if ($intro)<p class="mt-1">{{ $intro }}</p>@endif
        <ul class="mt-2 list-none space-y-1 p-0" data-summary-list>
            @foreach ($keys as $key)
                <li><a href="#{{ $targets[$key] ?? $forms->idFor($afterSave ? $key : null, $key) }}">{{ $errorBag->first($key) }}</a></li>
            @endforeach
        </ul>
    </div>
</div>
