<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New publication' : 'Edit publication'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-[1fr_10rem]">
            <x-admin.select-field name="form.type" label="Type" :options="$types" required />
            <x-admin.field name="form.year" label="Year" type="number" inputmode="numeric" />
        </div>
        <x-admin.field name="form.authors" label="Authors" hint="As they should appear in the citation." />
        <x-admin.field name="form.venue_publisher" label="Venue or publisher" />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.doi" label="DOI" placeholder="10.1000/xyz123" />
            <x-admin.field name="form.url" label="Link" type="url" placeholder="https://" />
        </div>
        <x-admin.textarea-field name="form.citation" label="Citation" rows="3" />
        <x-admin.textarea-field name="form.abstract" label="Abstract" rows="6" />
        @unless ($isNew)
            <x-admin.field name="form.slug" label="Slug" hint="Part of the public address. Leave as it is unless the link must change." class="font-mono" />
        @endunless

        <x-admin.file-field name="document" label="Full text file" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
