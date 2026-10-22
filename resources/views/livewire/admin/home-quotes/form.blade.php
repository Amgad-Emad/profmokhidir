<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New home quote' : 'Edit home quote'" intro="Published quotes appear in order on the home page, one in each evergreen band between the chapters of his life. Use his own words or a line from an approved tribute, never a paraphrase." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" class="mt-10">
        <x-admin.form-section id="quote" title="The quote">
            <x-form.textarea model="form.quote" label="Quote" rows="3" required :hint="'One or two sentences, '.\App\Constants\HomeTimeline::QUOTE_MAX_LENGTH.' characters at most. Leave out the quotation marks; they are added on the page.'" />
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input model="form.attribution" label="Attribution" hint="Who said or wrote it, if not the Professor." />
                <x-form.input model="form.source" label="Source" hint="For example the lecture, essay or tribute it comes from." />
            </div>
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
