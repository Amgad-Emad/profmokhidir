<div>
    <x-admin.page-header eyebrow="Media" :title="$isNew ? 'New video' : 'Edit video'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-3">
            <x-form.date model="form.date" label="Date" />
            <x-form.input model="form.event" label="Event" />
            <x-form.input model="form.location" label="Location" />
        </div>
        <x-form.textarea model="form.description" label="Description" rows="4" />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select model="form.decade" label="Decade" :options="$decades" placeholder="From the date" hint="Where the recording is filed when videos are grouped by decade. Leave empty to use its date." />
            <x-form.toggle model="form.is_featured" label="Featured recording" hint="Opens the Videos page as the lecture hall's first recording. Keep it to one or two." class="sm:self-end" />
        </div>
        <x-form.select model="form.provider" label="Source" :options="$providers" required live />
        <x-form.input model="form.media_url" label="Video link" type="url" placeholder="https://" hint="The YouTube, Vimeo or external page address. Not needed for an uploaded file." />
        <x-form.input model="form.captions_url" label="Captions link" type="url" placeholder="https://" hint="A WebVTT captions file, for accessible playback." />
        @unless ($isNew)
            <x-form.input model="form.slug" label="Slug" class="font-mono" />
        @endunless

        <x-form.upload model="videoFile" label="Uploaded video file" :media="$videoFile" :href="$videoFileUrl" :remove="$videoFile ? 'removeVideoFile' : null" :accept="$videoAccept" :hint="$videoHint" />

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>

    <div class="mt-14 max-w-3xl">
        <x-admin.image-grid label="Poster image" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
    </div>
</div>
