<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New award' : 'Edit award'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.name" label="Name" required />
        <div class="grid gap-6 sm:grid-cols-[1fr_10rem]">
            <x-form.input model="form.organisation" label="Awarding organisation" />
            <x-form.year model="form.year" label="Year" />
        </div>
        <x-form.textarea model="form.description" label="Description" rows="4" />
        <x-form.input model="form.source_url" label="Source link" type="url" placeholder="https://" />
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
