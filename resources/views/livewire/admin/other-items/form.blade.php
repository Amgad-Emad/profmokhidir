<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New item' : 'Edit item'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-3">
            <x-form.input model="form.category" label="Category" hint="Items are grouped by category." />
            <x-form.input model="form.item_type" label="Type" hint="e.g. interview, article, event" />
            <x-form.date model="form.date" label="Date" />
        </div>
        <x-form.textarea model="form.summary" label="Summary" rows="3" />
        <x-form.textarea model="form.body" label="Body" rows="8" hint="Shown on the item's own page when there is no destination link." />
        <x-form.input model="form.destination_url" label="Destination link" type="url" placeholder="https://" hint="When set, the item links out instead of opening its own page." />
        @unless ($isNew)
            <x-form.input model="form.slug" label="Slug" class="font-mono" />
        @endunless
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>

    <div class="mt-14 max-w-3xl">
        <x-admin.image-grid :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" multiple :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
    </div>
</div>
