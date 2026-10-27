@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New publication') : __('Edit publication')" :intro="__('A paper, book, chapter or report. Fill in what you know; the public page builds the citation from these fields.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['work' => __('The work'), 'citation' => __('Citation details'), 'file' => __('Full text'), 'catalogue' => __('Catalogue')]" class="mt-10">
        <x-admin.form-section id="work" :title="__('The work')">
            <x-admin.locale-tabs id="work-text" :label="__('The work')" :fields="['title', 'abstract']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="work-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('As printed, without a full stop at the end.')" />
                        <x-form.textarea :model="$field.'abstract'" :label="__('Abstract')" rows="6" :hint="__('The summary from the paper, if there is one.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <div class="form-row">
                <x-form.select class="form-col--two-thirds" model="form.type" :label="__('Type')" :options="$types" required :hint="__('Used by the Type filter on the public page.')" />
                <x-form.year class="form-col--third" model="form.year" :label="__('Year')" :hint="__('Year of publication.')" />
            </div>
            <x-form.input model="form.authors" :label="__('Authors')" :hint="__('As they should appear in the citation, separated by commas, for example Khidir, M. O., Hassan, A.')" />
        </x-admin.form-section>
        <x-admin.form-section id="citation" :title="__('Citation details')" :intro="__('Where the work appeared and how to find it. Leave out anything you do not have.')">
            <x-form.input model="form.venue_publisher" :label="__('Journal, venue or publisher')" :hint="__('For example Journal of Agricultural Science.')" />
            <div class="form-row">
                <x-form.input class="form-col--half" model="form.doi" :label="__('DOI')" placeholder="10.1000/xyz123" dir="ltr" :hint="__('The identifier that starts with 10. — it becomes a permanent link.')" />
                <x-form.input class="form-col--half" model="form.url" :label="__('Link')" type="url" placeholder="https://" :hint="__('A page where the work can be read.')" />
            </div>
            <x-form.textarea model="form.citation" :label="__('Citation as written')" rows="3" :hint="__('Kept for the record. The public page writes APA, MLA, Chicago and BibTeX from the fields above.')" />
        </x-admin.form-section>
        <x-admin.form-section id="file" :title="__('Full text')" :intro="__('A PDF readers can open from the publications page.')">
            <x-form.upload model="document" :label="__('Full text file')" :media="$file" :href="$fileUrl" :remove="$file ? 'removeDocument' : null" :accept="$fileAccept" :hint="$fileHint" />
        </x-admin.form-section>
        <x-admin.form-section id="catalogue" :title="__('Catalogue')">
            <x-form.input model="form.topic" :label="__('Topic')" :hint="__('The subject this work is filed under, used by the topic filter. Reuse an existing topic where one fits.')" list="publication-topics" />
            <datalist id="publication-topics">
                @foreach ($topics as $topic)
                    <option value="{{ $topic }}"></option>
                @endforeach
            </datalist>
            <x-form.toggle model="form.is_featured" :label="__('Show in Selected works')" :hint="__('Selected works open the publications page. Keep the selection to a handful.')" />
            @unless ($isNew)
                <x-form.input model="form.slug" :label="__('Web address')" :hint="__('The last part of the work\'s address. Leave it as it is unless the link must change.')" :mono="true" dir="ltr" />
            @endunless
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
