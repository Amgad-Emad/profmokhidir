<div>
    <x-admin.page-header eyebrow="Media" :title="$isNew ? 'New album' : 'Edit album'" intro="A group of photographs on the Pictures page. Every photo needs alt text and a caption with place and year before the album can be published." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['album' => 'The album', 'photos' => 'Photos']" class="mt-10">
        <x-admin.form-section id="album" title="The album">
            <x-form.input model="form.title" label="Title" required hint="For example Field trials in Gezira, 1972." />
            <x-form.textarea model="form.description" label="Description" rows="3" hint="A sentence or two shown above the photos." />
            @unless ($isNew)
                <x-form.input model="form.slug" label="Web address" hint="The last part of the album's address. Leave it as it is unless the link must change." class="font-mono" />
            @endunless
        </x-admin.form-section>

        <x-admin.form-section id="photos" title="Photos" :form="false">
            <x-admin.image-grid label="Photos" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" multiple orderable :cover-id="$coverId" :confirming-id="$confirmingImageId" :can-upload="! $isNew" :archive="$archive" :decades="$decades" />
            @if ($isNew)
                <p class="text-sm font-light text-ink-muted">Photos can be added once the album has a title and has been saved once.</p>
            @endif
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
