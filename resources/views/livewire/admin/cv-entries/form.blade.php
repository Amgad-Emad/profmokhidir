<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New CV entry' : 'Edit CV entry'" intro="One line of the CV: a degree, a post, a membership. The CV page groups entries by section." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" class="mt-10">
        <x-admin.form-section id="entry" title="The entry">
            <x-form.select model="form.section" label="Section" :options="$sections" required hint="Where on the CV page this belongs." />
            <x-form.input model="form.title" label="Title" required hint="The degree, position or achievement, for example PhD in Agronomy." />
            <x-form.input model="form.organisation" label="Organisation" hint="The university, ministry or society." />
            <x-form.textarea model="form.role_description" label="Description" rows="4" hint="A sentence or two about the role, if it needs explaining." />
        </x-admin.form-section>
        <x-admin.form-section id="dates" title="Dates">
            <div class="form-row">
                <x-form.year class="form-col--third" model="form.start_year" label="Start year" hint="Four digits." />
                <x-form.year class="form-col--third" model="form.end_year" label="End year" hint="Leave empty while it is ongoing." />
                <x-form.date class="form-col--third" model="form.last_updated_at" label="Last updated" hint="Only for long-running entries." />
            </div>
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
