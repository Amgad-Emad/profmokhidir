<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New home quote' : 'Edit home quote'" intro="Published quotes appear in order on the home page, one in each evergreen band between the chapters of his life. Use his own words or a line from an approved tribute, never a paraphrase." />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.textarea-field name="form.quote" label="Quote" rows="3" required :hint="'One or two sentences, '.\App\Constants\HomeTimeline::QUOTE_MAX_LENGTH.' characters at most. Leave out the quotation marks; they are added on the page.'" />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.attribution" label="Attribution" hint="Who said or wrote it, if not the professor." />
            <x-admin.field name="form.source" label="Source" hint="For example the lecture, essay or tribute it comes from." />
        </div>
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
