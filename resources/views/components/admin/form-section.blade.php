{{--
    One named part of a long form (Identity, Biography, Portrait ...): a Cormorant heading, an
    optional plain-language intro, then its fields. The id becomes the anchor #section-{id} that the
    edit screen's section nav links to. The body is a form whose submit saves the whole record
    (every field is bound to the same Livewire form); pass :form="false" for a section whose
    controls save themselves (the image grid), so its own forms are not nested.
--}}
@props(['id', 'title', 'intro' => null, 'form' => true])
<section id="section-{{ $id }}" {{ $attributes->class(['form-section']) }} aria-labelledby="section-{{ $id }}-title" data-form-section="{{ $id }}"{!! $form ? '' : ' data-not-content' !!}>
    <header class="form-section__header">
        <h2 id="section-{{ $id }}-title" class="form-section__title">{{ $title }}</h2>
        @if ($intro)
            <p class="form-section__intro">{{ $intro }}</p>
        @endif
    </header>
    @if ($form)
        <form wire:submit="save" class="form-section__body" novalidate>{{ $slot }}</form>
    @else
        <div class="form-section__body">{{ $slot }}</div>
    @endif
</section>
