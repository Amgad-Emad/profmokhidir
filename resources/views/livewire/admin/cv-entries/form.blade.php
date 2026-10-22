<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New CV entry' : 'Edit CV entry'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.select model="form.section" label="Section" :options="$sections" required />
        <x-form.input model="form.title" label="Title" required hint="The degree, position or achievement." />
        <x-form.input model="form.organisation" label="Organisation" />
        <x-form.textarea model="form.role_description" label="Description" rows="4" />
        <div class="grid gap-6 sm:grid-cols-3">
            <x-form.year model="form.start_year" label="Start year" />
            <x-form.year model="form.end_year" label="End year" hint="Leave empty for an ongoing entry." />
            <x-form.date model="form.last_updated_at" label="Last updated" />
        </div>
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
