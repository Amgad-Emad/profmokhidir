{{-- A small status chip for tables: a dot in the tone's colour and the status in words (so colour is never the only cue). --}}
@props(['tone', 'label'])
@php $tone = $tone instanceof \App\Constants\StatusTone ? $tone : \App\Constants\StatusTone::from($tone); @endphp
<span {{ $attributes->class(['status-chip', 'status-chip--'.$tone->value]) }} data-status="{{ $tone->value }}"><span class="status-chip__dot" aria-hidden="true"></span>{{ $label }}</span>
