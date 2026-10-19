<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New CV entry' : 'Edit CV entry'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.select-field name="form.section" label="Section" :options="$sections" required />
        <x-admin.field name="form.title" label="Title" required hint="The degree, position or achievement." />
        <x-admin.field name="form.organisation" label="Organisation" />
        <x-admin.textarea-field name="form.role_description" label="Description" rows="4" />
        <div class="grid gap-6 sm:grid-cols-3">
            <x-admin.field name="form.start_year" label="Start year" type="number" inputmode="numeric" />
            <x-admin.field name="form.end_year" label="End year" type="number" inputmode="numeric" hint="Leave empty for an ongoing entry." />
            <x-admin.field name="form.last_updated_at" label="Last updated" type="date" />
        </div>
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
