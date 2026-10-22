<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New commentary' : 'Edit commentary'" intro="An essay or note for the Commentary Corner. Visitors read it on its own page; there are no comments." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['opening' => 'Title & summary', 'text' => 'The text', 'topics' => 'Topics & links']" class="mt-10">
        <x-admin.form-section id="opening" title="Title & summary">
            <x-form.input model="form.title" label="Title" required hint="The heading readers see first." />
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.date model="form.date" label="Date" hint="When it was written or published." />
                <x-form.input model="form.byline" label="Byline" hint="Who wrote it, if not the Professor." />
            </div>
            <x-form.textarea model="form.summary" label="Summary" rows="3" hint="Two sentences shown in the list and in search results." />
            <div class="grid gap-6 sm:grid-cols-[2fr_1fr]">
                <x-form.textarea model="form.epigraph" label="Epigraph" rows="2" hint="An optional short quotation set under the title." />
                <x-form.input model="form.epigraph_attribution" label="Epigraph source" hint="Who said it." />
            </div>
        </x-admin.form-section>
        <x-admin.form-section id="text" title="The text">
            <x-form.editor model="form.body" label="Body" hint="Use the toolbar for headings (they build the table of contents), bold, italic, links, lists and quotes. For a sidenote, type [^1] in the text and start a paragraph with [^1]: and the note." />
        </x-admin.form-section>
        <x-admin.form-section id="topics" title="Topics & links">
            <x-form.tags model="form.tags" label="Topic tags" separator="," :suggestions="$tagSuggestions" hint="Readers can browse the corner by these topics. Press Enter after each one." />
            <x-form.input model="form.external_url" label="External link" type="url" placeholder="https://" hint="If the piece was first published elsewhere." />
            @unless ($isNew)
                <x-form.input model="form.slug" label="Web address" hint="The last part of the page's address. Leave it as it is unless the link must change." class="font-mono" />
            @endunless
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
