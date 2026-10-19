<div>
    <x-admin.page-header eyebrow="Media" :title="$isNew ? 'New video' : 'Edit video'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-6" novalidate>
        <x-admin.field name="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-3">
            <x-admin.field name="form.date" label="Date" type="date" />
            <x-admin.field name="form.event" label="Event" />
            <x-admin.field name="form.location" label="Location" />
        </div>
        <x-admin.textarea-field name="form.description" label="Description" rows="4" />
        <x-admin.select-field name="form.provider" label="Source" :options="$providers" required live />
        <x-admin.field name="form.media_url" label="Video link" type="url" placeholder="https://" hint="The YouTube, Vimeo or external page address. Not needed for an uploaded file." />
        <x-admin.field name="form.captions_url" label="Captions link" type="url" placeholder="https://" hint="A WebVTT captions file, for accessible playback." />
        @unless ($isNew)
            <x-admin.field name="form.slug" label="Slug" class="font-mono" />
        @endunless

        <x-admin.file-field name="videoFile" label="Uploaded video file" :media="$videoFile" :href="$videoFileUrl" :remove="$videoFile ? 'removeVideoFile' : null" :accept="$videoAccept" :hint="$videoHint" />

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>

    <div class="mt-14 max-w-3xl">
        <x-admin.image-grid label="Poster image" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
    </div>
</div>
