<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New publication' : 'Edit publication'" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-8" novalidate>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <x-form.input model="form.title" label="Title" required />
        <div class="grid gap-6 sm:grid-cols-[1fr_10rem]">
            <x-form.select model="form.type" label="Type" :options="$types" required />
            <x-form.year model="form.year" label="Year" />
        </div>
        <x-form.input model="form.authors" label="Authors" hint="As they should appear in the citation." />
        <x-form.input model="form.venue_publisher" label="Venue or publisher" />
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input model="form.doi" label="DOI" placeholder="10.1000/xyz123" />
            <x-form.input model="form.url" label="Link" type="url" placeholder="https://" />
        </div>
        <x-form.input model="form.topic" label="Topic" hint="The subject this work is catalogued under, used by the topic filter. Reuse an existing topic where one fits." list="publication-topics" />
        <datalist id="publication-topics">
            @foreach ($topics as $topic)
                <option value="{{ $topic }}"></option>
            @endforeach
        </datalist>
        <x-form.toggle model="form.is_featured" label="Show in Selected works" hint="Featured works open the publications page. Keep the selection to a handful." />
        <x-form.textarea model="form.citation" label="Citation" rows="3" hint="Kept for the record. The public page writes APA, MLA, Chicago and BibTeX from the fields above." />
        <x-form.textarea model="form.abstract" label="Abstract" rows="6" />
        @unless ($isNew)
            <x-form.input model="form.slug" label="Slug" hint="Part of the public address. Leave as it is unless the link must change." class="font-mono" />
        @endunless

        <x-form.upload model="document" label="Full text file" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />

        <x-admin.status-field :public-url="$publicUrl" />
        <x-admin.form-actions :index-url="$indexUrl" :public-url="$publicUrl" />
    </form>
</div>
