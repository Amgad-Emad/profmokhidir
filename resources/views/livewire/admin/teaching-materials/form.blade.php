<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New teaching material' : 'Edit teaching material'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.course" label="Course" />
            <x-admin.field name="form.topic" label="Topic" />
        </div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.select-field name="form.type" label="Type" :options="$types" required />
            <x-admin.select-field name="form.access" label="Who may download" :options="$accessOptions" required hint="Members-only files are stored privately and served through signed links." />
        </div>
        <x-admin.textarea-field name="form.description" label="Description" rows="4" />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-admin.field name="form.version" label="Version" />
            <x-admin.field name="form.material_date" label="Date" type="date" />
        </div>
        <x-admin.field name="form.external_url" label="External link" type="url" placeholder="https://" hint="Used when there is no file to download." />

        <x-admin.file-field name="document" label="File" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
