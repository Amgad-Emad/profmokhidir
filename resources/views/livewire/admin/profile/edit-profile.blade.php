<div>
    <x-admin.page-header eyebrow="Content" title="About & profile" intro="Everything on the About page: who he was, his biography, his fields of work, his portrait and how visitors can get in touch." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['identity' => 'Identity', 'biography' => 'Biography', 'expertise' => 'Expertise & key facts', 'portrait' => 'Portrait', 'contact' => 'Contact']" class="mt-10">
        <x-admin.form-section id="identity" title="Identity" intro="How he is named at the top of the About page and in search results.">
            <x-form.input model="form.name" label="Name" required hint="His full name, as it should appear on the site." />
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input model="form.title" label="Title" hint="For example Professor of Agronomy." />
                <x-form.input model="form.affiliation" label="Affiliation" hint="The university or institution he is known for." />
            </div>
        </x-admin.form-section>

        <x-admin.form-section id="biography" title="Biography">
            <x-form.textarea model="form.short_bio" label="Short biography" rows="3" hint="Two or three sentences. They open the home page and appear when the site is shared." />
            <x-form.editor model="form.full_bio" label="Full biography" hint="The life story on the About page. Use the toolbar for headings, bold, italic, links, lists and quotes." />
        </x-admin.form-section>

        <x-admin.form-section id="expertise" title="Expertise & key facts">
            <x-form.tags model="form.expertise" label="Expertise" hint="Each field of work as a short phrase, in the order they should appear. Press Enter after each one." data-expertise />

            <fieldset class="form-fieldset" data-key-facts>
                <legend class="t-label form-label">Key facts</legend>
                <p class="form-hint"><span class="form-hint__requirement">{{ \App\Constants\FormControls::OPTIONAL }}</span>{{ \App\Constants\FormControls::HINT_SEPARATOR }}A short label and its value, such as Born and 1940. They are listed beside the biography.</p>
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
        </x-admin.form-section>

        <x-admin.form-section id="portrait" title="Portrait" intro="The photograph that opens the About page and the sign-in screens. It needs alt text, a caption, a place and a year before the profile can be published." :form="false">
            <x-admin.image-grid label="Portrait" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
            @if ($isNew)
                <p class="text-sm font-light text-ink-muted">The portrait can be added once the profile has a name and has been saved once.</p>
            @endif
        </x-admin.form-section>

        <x-admin.form-section id="contact" title="Contact" intro="Shown at the foot of the About page. Leave both empty to use the site's contact details from Site settings.">
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input model="form.contact_email_public" label="Public contact email" type="email" hint="An address the family is happy to publish." />
                <x-form.input model="form.contact_note" label="Contact note" hint="For example Enquiries are answered by the family." />
            </div>
        </x-admin.form-section>

    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
