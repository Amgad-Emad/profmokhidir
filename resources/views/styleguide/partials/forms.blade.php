{{-- Forms: the shared control system (App\Constants\FormControls, FormStates), every state and every control in a light and a dark specimen side by side. --}}
@use('App\Constants\FormControls')
<section id="forms" class="py-20" aria-labelledby="forms-title" data-styleguide-forms>
    <hr class="rule-brass mb-10" data-rule>
    <p class="t-label text-accent-text" data-rise>03 · Forms</p>
    <h2 id="forms-title" class="t-heading mt-3" data-rise>One system for every field</h2>
    <div class="mt-6 grid gap-6 text-ink-muted md:grid-cols-3" data-rise-group>
        <p class="t-body">The label in the label style, the hint directly under it starting with <strong class="font-medium text-ink">{{ FormControls::REQUIRED }}</strong> or <strong class="font-medium text-ink">{{ FormControls::OPTIONAL }}</strong>, the field, then the error or the counter. Every control is {{ FormControls::MIN_HEIGHT_PX }}px or taller with {{ FormControls::TEXT_SIZE_PX }}px text, so phones never zoom.</p>
        <p class="t-body">Text fields show focus the same way for a click, a tap or the keyboard: the field's own line turns from 1px muted to {{ FormControls::FOCUS_LINE_PX }}px strong ink over {{ FormControls::FOCUS_LINE_MS }}ms, with a faint tint behind the field, and no outline, ring or box. Buttons, links, checkboxes, radios and toggles take a {{ FormControls::FOCUS_RING_PX }}px ring offset {{ FormControls::FOCUS_OFFSET_PX }}px for the keyboard only, never over the label.</p>
        <p class="t-body">Fields are checked when they are left and when the form is sent, never while typing. Each message names the fix. Long forms open with a summary that links to each field, and focus moves to the first field in error. The admin uses the boxed frame; the reading-room forms (sign-in, the application, tributes) the underline.</p>
    </div>

    <h3 class="mt-16 font-display text-3xl font-light" data-rise>Every state</h3>
    <div class="mt-8 grid gap-6 lg:grid-cols-2" data-form-specimens="states">
        <div class="form-specimen form-specimen--light" data-specimen="light">
            <p class="t-label form-label">Light</p>
            <div class="mt-6">@include('styleguide.partials.form-states', ['suffix' => 'light'])</div>
        </div>
        <div class="form-specimen form-specimen--dark" data-specimen="dark">
            <p class="t-label form-label">Dark</p>
            <div class="mt-6">@include('styleguide.partials.form-states', ['suffix' => 'dark'])</div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="form-specimen form-specimen--light form-style-underline" data-specimen="underline-light">
            <p class="t-label form-label">Underline style · light</p>
            <div class="mt-6">@include('styleguide.partials.form-states', ['suffix' => 'underline-light'])</div>
        </div>
        <div class="form-specimen form-specimen--dark form-style-underline" data-specimen="underline-dark">
            <p class="t-label form-label">Underline style · dark</p>
            <div class="mt-6">@include('styleguide.partials.form-states', ['suffix' => 'underline-dark'])</div>
        </div>
    </div>

    <h3 class="mt-16 font-display text-3xl font-light" data-rise>Every control</h3>
    <form novalidate class="mt-8 grid gap-6 lg:grid-cols-2" action="#forms" method="get" data-form-specimens="controls">
        <div class="form-specimen form-specimen--light" data-specimen="controls-light">
            <p class="t-label form-label">Light</p>
            <div class="mt-6">@include('styleguide.partials.form-set', ['suffix' => 'light'])</div>
        </div>
        <div class="form-specimen form-specimen--dark" data-specimen="controls-dark">
            <p class="t-label form-label">Dark</p>
            <div class="mt-6">@include('styleguide.partials.form-set', ['suffix' => 'dark'])</div>
        </div>
    </form>

    <h3 class="mt-16 font-display text-3xl font-light" data-rise>The form grid</h3>
    <p class="t-body mt-4 max-w-measure text-ink-muted" data-rise>Fields side by side sit in a <code class="font-mono text-sm">.form-row</code>: 12 columns from {{ \App\Constants\FormGrid::BREAKPOINT_PX }}px, one below. Each field lays its label, hint, control and message on shared subgrid rows, so the inputs line up whether or not a neighbour has a hint, and an error never pushes a neighbour down. Short fields take a half or a third; textareas the full row.</p>
    <form novalidate class="form-specimen form-specimen--light form-grid mt-8" action="#forms" method="get" data-form-grid-specimen>
        <div class="form-row">
            <x-form.input name="grid_email" id="grid-email" label="Public contact email" type="email" class="form-col--half" />
            <x-form.input name="grid_phone" id="grid-phone" label="Public contact phone" type="tel" hint="Digits, spaces and a leading + only." class="form-col--half" />
        </div>
        <div class="form-row">
            <x-form.input name="grid_place" id="grid-place" label="Place" class="form-col--two-thirds" error="Name the place, for example Khartoum." />
            <x-form.year name="grid_year" id="grid-year" label="Year" class="form-col--third" />
        </div>
        <x-form.textarea name="grid_note" id="grid-note" label="Note" rows="3" />
    </form>
</section>
