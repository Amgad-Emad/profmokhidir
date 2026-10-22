<div>
    <x-admin.page-header eyebrow="Content" title="About & profile" intro="The About page: name, title, biography, expertise and key facts." />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.name" label="Name" required />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input model="form.title" label="Title" hint="e.g. Professor of Agronomy" />
            <x-form.input model="form.affiliation" label="Affiliation" />
        </div>
        <x-form.textarea model="form.short_bio" label="Short biography" rows="3" hint="Two or three sentences for the home page." />
        <x-form.editor model="form.full_bio" label="Full biography" hint="Headings, bold, italic, links, lists and quotes." />

        <x-form.tags model="form.expertise" label="Expertise" hint="Each area as a short phrase, in the order they should appear." data-expertise />

        <fieldset class="form-fieldset" data-key-facts>
            <legend class="t-label form-label">Key facts</legend>
            <p class="form-hint"><span class="form-hint__requirement">{{ \App\Constants\FormControls::OPTIONAL }}</span>{{ \App\Constants\FormControls::HINT_SEPARATOR }}A short label and its value, such as Born and 1940.</p>
            <div class="mt-4 space-y-4">
                @foreach ($form['key_facts'] as $index => $fact)
                    <div class="grid items-end gap-4 sm:grid-cols-[1fr_2fr_auto]" wire:key="fact-{{ $index }}">
                        <x-form.input :model="'form.key_facts.'.$index.'.label'" :label="'Fact '.($index + 1).' label'" :optional="false" />
                        <x-form.input :model="'form.key_facts.'.$index.'.value'" :label="'Fact '.($index + 1).' value'" :optional="false" />
                        <button type="button" wire:click="removeKeyFact({{ $index }})" class="form-file__action form-file__action--remove mb-1">Remove<span class="sr-only"> key fact {{ $index + 1 }}</span></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addKeyFact" class="form-file__action mt-4 inline-flex items-center gap-2"><x-icon name="plus" class="size-4" />Add a key fact</button>
        </fieldset>

        <x-form.input model="form.students_supervised" label="Students supervised" type="number" inputmode="numeric" min="0" class="sm:max-w-xs" hint="Only if the family or university has confirmed the number. It appears in the figures on the home page; leave it blank to leave it out." />

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input model="form.contact_email_public" label="Public contact email" type="email" />
            <x-form.input model="form.contact_note" label="Contact note" />
        </div>

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)" :public-url="$publicUrl" save-label="Save profile" />
    </form>

    <div class="mt-14 max-w-3xl">
        <x-admin.image-grid label="Portrait" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
    </div>
</div>
