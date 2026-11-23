{{--
    One named part of a long form (Identity, Biography, Portrait ...): a Cormorant heading, an
    optional plain-language intro, then its fields. The id becomes the anchor #section-{id} that the
    edit screen's section nav links to. The body is a form whose submit saves the whole record
    (every field is bound to the same Livewire form); pass :form="false" for a section whose
    controls save themselves (the image grid), so its own forms are not nested. A form section's
    body is the form grid (App\Constants\FormGrid): fields span the full row unless they carry a
    width class (form-col--half, --third ...), and fields side by side line up on subgrid rows.
    The heading is a button that folds the section away (Alpine `collapsible`, remembered per viewer);
    it opens again when the section nav, an error link or the keyboard reaches a field inside it.
    `collapsed` starts it folded on every visit (the page editor), instead of remembering the choice.
    The `actions` slot sits at the end of the heading row (the page editor's "Show on the page" switch).
--}}
@props(['id', 'title', 'intro' => null, 'form' => true, 'collapsed' => false])
<section id="section-{{ $id }}" {{ $attributes->class(['form-section', 'form-section--collapsed' => $collapsed]) }} aria-labelledby="section-{{ $id }}-title" data-form-section="{{ $id }}"{!! $form ? '' : ' data-not-content' !!}
         x-data="collapsible('section-{{ $id }}', {{ $collapsed ? "false, false, 'sections'" : 'true, true' }})" x-on:focusin="focusIn($event)" x-bind:class="{ 'form-section--collapsed': ! open }">
    <header class="form-section__header">
        <div class="form-section__heading-row">
        <h2 id="section-{{ $id }}-title" class="form-section__title">
            <button type="button" class="form-section__toggle" x-on:click="toggle()" aria-expanded="{{ $collapsed ? 'false' : 'true' }}" x-bind:aria-expanded="open.toString()" aria-controls="section-{{ $id }}-body">
                <span>{{ $title }}</span>
                <x-icon name="chevron-down" class="form-section__chevron size-5" />
            </button>
        </h2>
        @isset($actions)
            <div class="form-section__actions">{{ $actions }}</div>
        @endisset
        </div>
        @if ($intro)
            <p class="form-section__intro">{{ $intro }}</p>
        @endif
    </header>
    <div id="section-{{ $id }}-body" @class(['fold', 'fold--open fold--settled' => ! $collapsed]) x-bind:class="{ 'fold--open': open, 'fold--settled': settled }" x-bind:inert="! open" @if ($collapsed) inert @endif data-collapsible-body>
        <div class="fold__inner">
            @if ($form)
                <form wire:submit="save" class="form-section__body form-grid" novalidate>{{ $slot }}</form>
            @else
                <div class="form-section__body">{{ $slot }}</div>
            @endif
        </div>
    </div>
</section>
