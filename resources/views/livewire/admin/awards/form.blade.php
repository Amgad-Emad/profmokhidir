@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New award') : __('Edit award')" :intro="__('An honour, prize or fellowship. It appears on the Awards page, newest first.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" class="mt-10">
        <x-admin.form-section id="award" :title="__('The award')">
            <x-admin.locale-tabs id="award-text" :label="__('The award')" :fields="['name', 'organisation', 'description']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="award-text" :locale="$locale">
                        <x-form.input :model="$field.'name'" :label="__('Name of the award')" :required="$locale === Locales::DEFAULT" :hint="__('As the awarding body writes it, for example Order of Merit in Science.')" />
                        <x-form.input :model="$field.'organisation'" :label="__('Awarding organisation')" :hint="__('Who gave it.')" />
                        <x-form.textarea :model="$field.'description'" :label="__('Description')" rows="4" :hint="__('One or two sentences on what it recognised. Leave empty if the name says enough.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <div class="form-row">
                <x-form.year class="form-col--third" model="form.year" :label="__('Year')" :hint="__('Four digits.')" />
            </div>
            <x-form.input model="form.source_url" :label="__('Source link')" type="url" placeholder="https://" :hint="__('A page that confirms the award, if there is one online.')" />
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
