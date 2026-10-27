@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New CV entry') : __('Edit CV entry')" :intro="__('One line of the CV: a degree, a post, a membership. The CV page groups entries by section.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" class="mt-10">
        <x-admin.form-section id="entry" :title="__('The entry')">
            <x-form.select model="form.section" :label="__('Section')" :options="$sections" required :hint="__('Where on the CV page this belongs.')" />
            <x-admin.locale-tabs id="entry-text" :label="__('The entry')" :fields="['title', 'organisation', 'role_description']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="entry-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('The degree, position or achievement, for example PhD in Agronomy.')" />
                        <x-form.input :model="$field.'organisation'" :label="__('Organisation')" :hint="__('The university, ministry or society.')" />
                        <x-form.textarea :model="$field.'role_description'" :label="__('Description')" rows="4" :hint="__('A sentence or two about the role, if it needs explaining.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
        </x-admin.form-section>
        <x-admin.form-section id="dates" :title="__('Dates')">
            <div class="form-row">
                <x-form.year class="form-col--third" model="form.start_year" :label="__('Start year')" :hint="__('Four digits.')" />
                <x-form.year class="form-col--third" model="form.end_year" :label="__('End year')" :hint="__('Leave empty while it is ongoing.')" />
                <x-form.date class="form-col--third" model="form.last_updated_at" :label="__('Last updated')" :hint="__('Only for long-running entries.')" />
            </div>
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
