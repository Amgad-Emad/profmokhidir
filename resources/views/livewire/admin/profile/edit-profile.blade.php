<div>
    <x-admin.page-header eyebrow="Content" title="About & profile" intro="The About page: name, title, biography, expertise and key facts." />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.name" label="Name" required />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.title" label="Title" hint="e.g. Professor of Agronomy" />
            <x-admin.field name="form.affiliation" label="Affiliation" />
        </div>
        <x-admin.textarea-field name="form.short_bio" label="Short biography" rows="3" hint="Two or three sentences for the home page." />
        <x-admin.rich-text name="form.full_bio" label="Full biography" hint="Headings, bold, italic, links, lists and quotes." />

        <fieldset data-expertise>
            <legend class="t-label text-bronze">Expertise</legend>
            <div class="mt-3 space-y-3">
                @foreach ($form['expertise'] as $index => $item)
                    <div class="flex items-start gap-3" wire:key="expertise-{{ $index }}">
                        <x-admin.field :name="'form.expertise.'.$index" :label="'Expertise '.($index + 1)" class="flex-1 [&>label]:sr-only [&>input]:mt-0" />
                        <button type="button" wire:click="removeExpertise({{ $index }})" class="mt-3 text-sm text-bronze underline-offset-4 hover:underline">Remove<span class="sr-only"> expertise {{ $index + 1 }}</span></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addExpertise" class="mt-3 text-sm text-evergreen underline decoration-brass underline-offset-4">Add an area of expertise</button>
        </fieldset>

        <fieldset data-key-facts>
            <legend class="t-label text-bronze">Key facts</legend>
            <div class="mt-3 space-y-3">
                @foreach ($form['key_facts'] as $index => $fact)
                    <div class="grid gap-3 sm:grid-cols-[1fr_2fr_auto]" wire:key="fact-{{ $index }}">
                        <x-admin.field :name="'form.key_facts.'.$index.'.label'" label="Label" placeholder="Label" class="[&>label]:sr-only [&>input]:mt-0" />
                        <x-admin.field :name="'form.key_facts.'.$index.'.value'" label="Value" placeholder="Value" class="[&>label]:sr-only [&>input]:mt-0" />
                        <button type="button" wire:click="removeKeyFact({{ $index }})" class="mt-3 text-sm text-bronze underline-offset-4 hover:underline">Remove<span class="sr-only"> key fact {{ $index + 1 }}</span></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addKeyFact" class="mt-3 text-sm text-evergreen underline decoration-brass underline-offset-4">Add a key fact</button>
        </fieldset>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.contact_email_public" label="Public contact email" type="email" />
            <x-admin.field name="form.contact_note" label="Contact note" />
        </div>

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)" :public-url="$publicUrl" save-label="Save profile" />
    </form>

    <div class="mt-14 max-w-3xl">
        <x-admin.image-grid label="Portrait" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
    </div>
</div>
