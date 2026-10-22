<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New teaching material' : 'Edit teaching material'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input model="form.course" label="Course" />
            <x-form.input model="form.topic" label="Topic" />
        </div>
        <x-form.textarea model="form.course_description" label="Course description" rows="2" hint="A sentence or two under the course heading on the syllabus. Only one material per course needs it; the first filled in is used." />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select model="form.type" label="Type" :options="$types" required />
            <x-form.select model="form.access" label="Who may download" :options="$accessOptions" required hint="Members-only files are stored privately and served through signed links." />
        </div>
        <x-form.textarea model="form.description" label="Description" rows="4" />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input model="form.version" label="Version" />
            <x-form.date model="form.material_date" label="Date" />
        </div>
        <x-form.input model="form.external_url" label="External link" type="url" placeholder="https://" hint="Used when there is no file to download." />

        <x-form.upload model="document" label="File" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
