<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New item' : 'Edit item'" intro="Something that belongs on the record but fits no other section: an interview, a dedication, an event. Items are grouped by category." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['item' => 'The item', 'text' => 'Text & link', 'images' => 'Images']" class="mt-10">
        <x-admin.form-section id="item" title="The item">
            <x-form.input model="form.title" label="Title" required hint="The heading readers see." />
            <div class="form-row">
                <x-form.input class="form-col--third" model="form.category" label="Category" hint="Items with the same category are shown together." />
                <x-form.input class="form-col--third" model="form.item_type" label="Type" hint="For example interview, article or event." />
                <x-form.date class="form-col--third" model="form.date" label="Date" hint="When it happened." />
            </div>
            <x-form.textarea model="form.summary" label="Summary" rows="3" hint="One or two sentences for the list." />
        </x-admin.form-section>
        <x-admin.form-section id="text" title="Text & link">
            <x-form.textarea model="form.body" label="Body" rows="8" hint="Shown on the item's own page. Not needed when the item links elsewhere." />
            <x-form.input model="form.destination_url" label="Destination link" type="url" placeholder="https://" hint="When set, the item links out to this address instead of opening its own page." />
            @unless ($isNew)
                <x-form.input model="form.slug" label="Web address" hint="The last part of the page's address. Leave it as it is unless the link must change." class="font-mono" />
            @endunless
        </x-admin.form-section>

        <x-admin.form-section id="images" title="Images" :form="false">
            <x-admin.image-grid :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" multiple :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
            @if ($isNew)
                <p class="text-sm font-light text-ink-muted">Images can be added once the item has a title and has been saved once.</p>
            @endif
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
