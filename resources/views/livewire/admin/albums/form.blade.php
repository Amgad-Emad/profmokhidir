<div>
    <x-admin.page-header eyebrow="Media" :title="$isNew ? 'New album' : 'Edit album'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.title" label="Title" required />
        <x-admin.textarea-field name="form.description" label="Description" rows="3" />
        @unless ($isNew)
            <x-admin.field name="form.slug" label="Slug" class="font-mono" />
        @endunless
        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>

    <div class="mt-14 max-w-4xl">
        <x-admin.image-grid label="Photos" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" multiple orderable :cover-id="$coverId" :confirming-id="$confirmingImageId" :can-upload="! $isNew" :archive="$archive" :decades="$decades" />
    </div>
</div>
