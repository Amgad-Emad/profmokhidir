{{--
    One calm note at the top of a form when validation fails: ash on linen with an icon,
    the title, and each message as a link to its field (ids match field names; pass
    `targets` to point a key elsewhere, e.g. ['code' => 'code-1'] for the digit boxes).
    Focused when the page loads so screen readers hear it first. Renders nothing without errors.
--}}
@props(['bag' => 'default', 'targets' => [], 'title' => \App\Constants\AuthScreens::ERROR_SUMMARY_TITLE])
@php $errorBag = $errors->getBag($bag); @endphp
@if ($errorBag->any())
    <div {{ $attributes->class(['auth-note']) }} role="alert" tabindex="-1" data-error-summary x-data x-init="$el.focus()">
        <x-icon name="warning" class="mt-0.5 size-5 shrink-0" />
        <div>
            <p class="auth-note__title">{{ $title }}</p>
            <ul class="mt-1 list-none space-y-1 p-0">
                @foreach ($errorBag->keys() as $key)
                    <li><a href="#{{ $targets[$key] ?? $key }}">{{ $errorBag->first($key) }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
