<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New award' : 'Edit award'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.name" label="Name" required />
        <div class="grid gap-6 sm:grid-cols-[1fr_10rem]">
            <x-admin.field name="form.organisation" label="Awarding organisation" />
            <x-admin.field name="form.year" label="Year" type="number" inputmode="numeric" />
        </div>
        <x-admin.textarea-field name="form.description" label="Description" rows="4" />
        <x-admin.field name="form.source_url" label="Source link" type="url" placeholder="https://" />
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
