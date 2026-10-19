{{-- Small inline line icons (1.5px stroke, currentColor) so state is never shown by colour alone. Decorative by default; pass a `label` to expose it to screen readers. --}}
@props(['name', 'label' => null])
@php $hasSize = preg_match('/\bsize-/', (string) $attributes->get('class')) === 1; @endphp
<svg {{ $attributes->class(['inline-block shrink-0', 'size-5' => ! $hasSize]) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"{!! $label ? ' role="img" aria-label="'.e($label).'"' : ' aria-hidden="true" focusable="false"' !!}>
@switch($name)
    @case('check')
        <path d="M5 12.5l4.5 4.5L19 7.5" />
        @break
    @case('clock')
        <circle cx="12" cy="12" r="8.25" /><path d="M12 7.5V12l3 2" />
        @break
    @case('minus')
        <path d="M6 12h12" />
        @break
    @case('warning')
        <path d="M12 4.5l8.5 15h-17z" /><path d="M12 10v4" /><path d="M12 17.25h.01" />
        @break
    @case('info')
        <circle cx="12" cy="12" r="8.25" /><path d="M12 11v5" /><path d="M12 8h.01" />
        @break
    @case('menu')
        <path d="M4 7h16" /><path d="M4 12h16" /><path d="M4 17h16" />
        @break
    @case('close')
        <path d="M6 6l12 12" /><path d="M18 6L6 18" />
        @break
    @case('arrow')
        <path d="M5 12h14" /><path d="M13 6l6 6-6 6" />
        @break
    @case('shield')
        <path d="M12 3.5l7 2.75v5.25c0 4.5-3 7.75-7 9-4-1.25-7-4.5-7-9V6.25z" /><path d="M9.5 12.25l1.75 1.75 3.5-3.75" />
        @break
    @case('user')
        <circle cx="12" cy="8.5" r="3.5" /><path d="M5 19.5c0-3.5 3.1-5.5 7-5.5s7 2 7 5.5" />
        @break
    @case('lock')
        <rect x="5.5" y="10.5" width="13" height="9" rx="1" /><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5" />
        @break
    @default
        <circle cx="12" cy="12" r="8.25" />
@endswitch
</svg>
