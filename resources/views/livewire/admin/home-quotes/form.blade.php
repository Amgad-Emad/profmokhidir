@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New home quote') : __('Edit home quote')" :intro="__('Published quotes appear in order on the home page, one in each evergreen band between the chapters of his life. Use his own words or a line from an approved tribute, never a paraphrase.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" class="mt-10">
        <x-admin.form-section id="quote" :title="__('The quote')">
            <x-admin.locale-tabs id="quote-text" :label="__('The quote')" :fields="['quote', 'attribution']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="quote-text" :locale="$locale">
                        <x-form.textarea :model="$field.'quote'" :label="__('Quote')" rows="3" :required="$locale === Locales::DEFAULT" :hint="__('One or two sentences, :max characters at most. Leave out the quotation marks; they are added on the page.', ['max' => \App\Constants\HomeTimeline::QUOTE_MAX_LENGTH])" />
                        <x-form.input :model="$field.'attribution'" :label="__('Attribution')" :hint="__('Who said or wrote it, if not the Professor.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <x-form.input model="form.source" :label="__('Source')" :hint="__('For example the lecture, essay or tribute it comes from.')" />
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
