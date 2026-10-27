@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New commentary') : __('Edit commentary')" :intro="__('An essay or note for the Commentary Corner. Visitors read it on its own page; there are no comments.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['opening' => __('Title & summary'), 'text' => __('The text'), 'topics' => __('Topics & links')]" class="mt-10">
        <x-admin.form-section id="opening" :title="__('Title & summary')">
            <x-admin.locale-tabs id="opening-text" :label="__('Title & summary')" :fields="['title', 'summary', 'epigraph']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="opening-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('The heading readers see first.')" />
                        <x-form.textarea :model="$field.'summary'" :label="__('Summary')" rows="3" :hint="__('Two sentences shown in the list and in search results.')" />
                        <x-form.textarea :model="$field.'epigraph'" :label="__('Epigraph')" rows="2" :hint="__('An optional short quotation set under the title.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <div class="form-row">
                <x-form.date class="form-col--half" model="form.date" :label="__('Date')" :hint="__('When it was written or published.')" />
                <x-form.input class="form-col--half" model="form.byline" :label="__('Byline')" :hint="__('Who wrote it, if not the Professor.')" />
            </div>
            <div class="form-row">
                <x-form.input class="form-col--half" model="form.epigraph_attribution" :label="__('Epigraph source')" :hint="__('Who said it.')" />
            </div>
        </x-admin.form-section>
        <x-admin.form-section id="text" :title="__('The text')">
            <x-admin.locale-tabs id="body-text" :label="__('The text')" :fields="['body']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="body-text" :locale="$locale">
                        <x-form.editor :model="$field.'body'" :label="__('Body')" :placeholder="__('Start writing…')" :hint="__('Use the toolbar for headings (they build the table of contents), bold, italic, links, lists and quotes. For a sidenote, type [^1] in the text and start a paragraph with [^1]: and the note.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
        </x-admin.form-section>
        <x-admin.form-section id="topics" :title="__('Topics & links')">
            <x-form.tags model="form.tags" :label="__('Topic tags')" separator="," :suggestions="$tagSuggestions" :hint="__('Readers can browse the corner by these topics. Press Enter after each one.')" />
            <x-form.input model="form.external_url" :label="__('External link')" type="url" placeholder="https://" :hint="__('If the piece was first published elsewhere.')" />
            @unless ($isNew)
                <x-form.input model="form.slug" :label="__('Web address')" :hint="__('The last part of the page\'s address. Leave it as it is unless the link must change.')" :mono="true" dir="ltr" />
            @endunless
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
