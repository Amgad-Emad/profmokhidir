<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New commentary' : 'Edit commentary'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.date model="form.date" label="Date" />
            <x-form.input model="form.byline" label="Byline" />
        </div>
        <x-form.textarea model="form.summary" label="Summary" rows="3" hint="Shown in the list and as the page description." />
        <div class="grid gap-6 sm:grid-cols-[2fr_1fr]">
            <x-form.textarea model="form.epigraph" label="Epigraph" rows="2" hint="An optional short quotation set under the title." />
            <x-form.input model="form.epigraph_attribution" label="Epigraph source" />
        </div>
        <x-form.editor model="form.body" label="Body" hint="Headings (they make the table of contents), bold, italic, links, lists and quotes. For a sidenote, type a marker such as [^1] in the text and a paragraph starting [^1]: with the note." />
        <x-form.input model="form.external_url" label="External link" type="url" placeholder="https://" hint="If the piece was published elsewhere." />
        <x-form.tags model="form.tags" label="Topic tags" separator="," :suggestions="$tagSuggestions" hint="Readers can browse the corner by these topics." />
        @unless ($isNew)
            <x-form.input model="form.slug" label="Slug" hint="Part of the public address. Leave as it is unless the link must change." class="font-mono" />
        @endunless
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
