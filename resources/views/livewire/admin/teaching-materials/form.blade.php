<div>
    <x-admin.page-header eyebrow="Content" :title="$isNew ? 'New teaching material' : 'Edit teaching material'" intro="Lecture notes, a syllabus or a problem set. The Teaching page groups materials by course." />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['material' => 'The material', 'course' => 'Course', 'file' => 'File & access']" class="mt-10">
        <x-admin.form-section id="material" title="The material">
            <x-form.input model="form.title" label="Title" required hint="For example Lecture 3: Soil water." />
            <x-form.select model="form.type" label="Type" :options="$types" required hint="What kind of material it is." />
            <x-form.textarea model="form.description" label="Description" rows="4" hint="What a student will find in it." />
            <div class="form-row">
                <x-form.input class="form-col--half" model="form.version" label="Version" hint="For example 2nd edition." />
                <x-form.date class="form-col--half" model="form.material_date" label="Date" hint="When this version was written." />
            </div>
        </x-admin.form-section>
        <x-admin.form-section id="course" title="Course">
            <div class="form-row">
                <x-form.input class="form-col--half" model="form.course" label="Course" hint="Materials with the same course are shown together." />
                <x-form.input class="form-col--half" model="form.topic" label="Topic" hint="A finer grouping inside the course." />
            </div>
            <x-form.textarea model="form.course_description" label="Course description" rows="2" hint="A sentence or two under the course heading. Only one material per course needs it; the first filled in is used." />
        </x-admin.form-section>
        <x-admin.form-section id="file" title="File & access">
            <x-form.upload model="document" label="File" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />
            <x-form.input model="form.external_url" label="External link" type="url" placeholder="https://" hint="Only when there is no file to upload." />
            <x-form.select model="form.access" label="Who may download" :options="$accessOptions" required hint="Members-only files are kept private and opened through a link that expires." />
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
