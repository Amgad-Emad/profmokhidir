<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New commentary' : 'Edit commentary'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.date" label="Date" type="date" />
            <x-admin.field name="form.byline" label="Byline" />
        </div>
        <x-admin.textarea-field name="form.summary" label="Summary" rows="3" hint="Shown in the list and as the page description." />
        <x-admin.rich-text name="form.body" label="Body" hint="Headings, bold, italic, links, lists and quotes." />
        <x-admin.field name="form.external_url" label="External link" type="url" placeholder="https://" hint="If the piece was published elsewhere." />
        <x-admin.field name="form.tags" label="Topic tags" hint="Separate tags with commas." />
        @unless ($isNew)
            <x-admin.field name="form.slug" label="Slug" hint="Part of the public address. Leave as it is unless the link must change." class="font-mono" />
        @endunless
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
