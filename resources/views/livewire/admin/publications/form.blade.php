<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New publication' : 'Edit publication'" intro="A paper, book, chapter or report. Fill in what you know; the public page builds the citation from these fields." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['work' => 'The work', 'citation' => 'Citation details', 'file' => 'Full text', 'catalogue' => 'Catalogue']" class="mt-10">
        <x-admin.form-section id="work" title="The work">
            <x-form.input model="form.title" label="Title" required hint="As printed, without a full stop at the end." />
            <div class="grid gap-6 sm:grid-cols-[1fr_10rem]">
                <x-form.select model="form.type" label="Type" :options="$types" required hint="Used by the Type filter on the public page." />
                <x-form.year model="form.year" label="Year" hint="Year of publication." />
            </div>
            <x-form.input model="form.authors" label="Authors" hint="As they should appear in the citation, separated by commas, for example Khidir, M. O., Hassan, A." />
            <x-form.textarea model="form.abstract" label="Abstract" rows="6" hint="The summary from the paper, if there is one." />
        </x-admin.form-section>
        <x-admin.form-section id="citation" title="Citation details" intro="Where the work appeared and how to find it. Leave out anything you do not have.">
            <x-form.input model="form.venue_publisher" label="Journal, venue or publisher" hint="For example Journal of Agricultural Science." />
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input model="form.doi" label="DOI" placeholder="10.1000/xyz123" hint="The identifier that starts with 10. — it becomes a permanent link." />
                <x-form.input model="form.url" label="Link" type="url" placeholder="https://" hint="A page where the work can be read." />
            </div>
            <x-form.textarea model="form.citation" label="Citation as written" rows="3" hint="Kept for the record. The public page writes APA, MLA, Chicago and BibTeX from the fields above." />
        </x-admin.form-section>
        <x-admin.form-section id="file" title="Full text" intro="A PDF readers can open from the publications page.">
            <x-form.upload model="document" label="Full text file" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />
        </x-admin.form-section>
        <x-admin.form-section id="catalogue" title="Catalogue">
            <x-form.input model="form.topic" label="Topic" hint="The subject this work is filed under, used by the topic filter. Reuse an existing topic where one fits." list="publication-topics" />
            <datalist id="publication-topics">
                @foreach ($topics as $topic)
                    <option value="{{ $topic }}"></option>
                @endforeach
            </datalist>
            <x-form.toggle model="form.is_featured" label="Show in Selected works" hint="Selected works open the publications page. Keep the selection to a handful." />
            @unless ($isNew)
                <x-form.input model="form.slug" label="Web address" hint="The last part of the work's address. Leave it as it is unless the link must change." class="font-mono" />
            @endunless
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
