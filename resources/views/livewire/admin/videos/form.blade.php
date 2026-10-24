<div>
    <x-admin.page-header eyebrow="Media" :title="$isNew ? 'New video' : 'Edit video'" intro="A lecture, interview or ceremony. Link to YouTube or Vimeo, or upload the file." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['video' => 'The recording', 'source' => 'Where it plays', 'filing' => 'Filing', 'poster' => 'Poster image']" class="mt-10">
        <x-admin.form-section id="video" title="The recording">
            <x-form.input model="form.title" label="Title" required hint="For example Inaugural lecture, University of Khartoum." />
            <div class="form-row">
                <x-form.date class="form-col--third" model="form.date" label="Date" hint="When it was recorded." />
                <x-form.input class="form-col--third" model="form.event" label="Event" hint="The occasion." />
                <x-form.input class="form-col--third" model="form.location" label="Location" hint="City or hall." />
            </div>
            <x-form.textarea model="form.description" label="Description" rows="4" hint="What the recording is about, in two or three sentences." />
        </x-admin.form-section>
        <x-admin.form-section id="source" title="Where it plays">
            <x-form.select model="form.provider" label="Source" :options="$providers" required live hint="Where the video is kept." />
            <x-form.input model="form.media_url" label="Video link" type="url" placeholder="https://" hint="The YouTube, Vimeo or other page address. Not needed for an uploaded file." />
            <x-form.upload model="videoFile" label="Uploaded video file" :media="$videoFile" :href="$videoFileUrl" :remove="$videoFile ? 'removeVideoFile' : null" :accept="$videoAccept" :hint="$videoHint" />
            <x-form.input model="form.captions_url" label="Captions link" type="url" placeholder="https://" hint="A WebVTT captions file, so people who cannot hear can follow." />
        </x-admin.form-section>
        <x-admin.form-section id="filing" title="Filing">
            <div class="form-row">
                <x-form.select class="form-col--half" model="form.decade" label="Decade" :options="$decades" placeholder="From the date" hint="Where the recording is filed when videos are grouped by decade. Leave empty to use its date." />
                <x-form.toggle model="form.is_featured" label="Featured recording" hint="Opens the Videos page. Keep it to one or two." class="form-col--half" />
            </div>
            @unless ($isNew)
                <x-form.input model="form.slug" label="Web address" hint="The last part of the page's address. Leave it as it is unless the link must change." class="font-mono" />
            @endunless
        </x-admin.form-section>

        <x-admin.form-section id="poster" title="Poster image" :form="false">
            <x-admin.image-grid label="Poster image" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
            @if ($isNew)
                <p class="text-sm font-light text-ink-muted">The poster can be added once the video has a title and has been saved once.</p>
            @endif
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
