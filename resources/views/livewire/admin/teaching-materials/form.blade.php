@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New teaching material') : __('Edit teaching material')" :intro="__('Lecture notes, a syllabus or a problem set. The Teaching page groups materials by course.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['material' => __('The material'), 'course' => __('Course'), 'file' => __('File & access')]" class="mt-10">
        <x-admin.form-section id="material" :title="__('The material')">
            <x-admin.locale-tabs id="material-text" :label="__('The material')" :fields="['title', 'description']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="material-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('For example Lecture 3: Soil water.')" />
                        <x-form.textarea :model="$field.'description'" :label="__('Description')" rows="4" :hint="__('What a student will find in it.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <x-form.select model="form.type" :label="__('Type')" :options="$types" required :hint="__('What kind of material it is.')" />
            <div class="form-row">
                <x-form.input class="form-col--half" model="form.version" :label="__('Version')" :hint="__('For example 2nd edition.')" />
                <x-form.date class="form-col--half" model="form.material_date" :label="__('Date')" :hint="__('When this version was written.')" />
            </div>
        </x-admin.form-section>
        <x-admin.form-section id="course" :title="__('Course')">
            <div class="form-row">
                <x-form.input class="form-col--half" model="form.course" :label="__('Course')" :hint="__('Materials with the same course are shown together.')" />
                <x-form.input class="form-col--half" model="form.topic" :label="__('Topic')" :hint="__('A finer grouping inside the course.')" />
            </div>
            <x-admin.locale-tabs id="course-text" :label="__('Course')" :fields="['course_description']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="course-text" :locale="$locale">
                        <x-form.textarea :model="$field.'course_description'" :label="__('Course description')" rows="2" :hint="__('A sentence or two under the course heading. Only one material per course needs it; the first filled in is used.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
        </x-admin.form-section>
        <x-admin.form-section id="file" :title="__('File & access')">
            <x-form.upload model="document" :label="__('File')" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />
            <x-form.input model="form.external_url" :label="__('External link')" type="url" placeholder="https://" :hint="__('Only when there is no file to upload.')" />
            <x-form.select model="form.access" :label="__('Who may download')" :options="$accessOptions" required :hint="__('Members-only files are kept private and opened through a link that expires.')" />
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
