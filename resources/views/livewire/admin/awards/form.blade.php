<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New award' : 'Edit award'" intro="An honour, prize or fellowship. It appears on the Awards page, newest first." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" class="mt-10">
        <x-admin.form-section id="award" title="The award">
            <x-form.input model="form.name" label="Name of the award" required hint="As the awarding body writes it, for example Order of Merit in Science." />
            <div class="grid gap-6 sm:grid-cols-[1fr_10rem]">
                <x-form.input model="form.organisation" label="Awarding organisation" hint="Who gave it." />
                <x-form.year model="form.year" label="Year" hint="Four digits." />
            </div>
            <x-form.textarea model="form.description" label="Description" rows="4" hint="One or two sentences on what it recognised. Leave empty if the name says enough." />
            <x-form.input model="form.source_url" label="Source link" type="url" placeholder="https://" hint="A page that confirms the award, if there is one online." />
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
