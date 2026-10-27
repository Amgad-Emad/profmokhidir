@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="$isNew ? __('New item') : __('Edit item')" :intro="__('Something that belongs on the record but fits no other section: an interview, a dedication, an event. Items are grouped by category.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['item' => __('The item'), 'text' => __('Text & link'), 'images' => __('Images')]" class="mt-10">
        <x-admin.form-section id="item" :title="__('The item')">
            <x-admin.locale-tabs id="item-text" :label="__('The item')" :fields="['title', 'summary']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="item-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('The heading readers see.')" />
                        <x-form.textarea :model="$field.'summary'" :label="__('Summary')" rows="3" :hint="__('One or two sentences for the list.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <div class="form-row">
                <x-form.input class="form-col--third" model="form.category" :label="__('Category')" :hint="__('Items with the same category are shown together.')" />
                <x-form.input class="form-col--third" model="form.item_type" :label="__('Type')" :hint="__('For example interview, article or event.')" />
                <x-form.date class="form-col--third" model="form.date" :label="__('Date')" :hint="__('When it happened.')" />
            </div>
        </x-admin.form-section>
        <x-admin.form-section id="text" :title="__('Text & link')">
            <x-admin.locale-tabs id="item-body" :label="__('Text & link')" :fields="['body']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="item-body" :locale="$locale">
                        <x-form.textarea :model="$field.'body'" :label="__('Body')" rows="8" :hint="__('Shown on the item\'s own page. Not needed when the item links elsewhere.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <x-form.input model="form.destination_url" :label="__('Destination link')" type="url" placeholder="https://" :hint="__('When set, the item links out to this address instead of opening its own page.')" />
            @unless ($isNew)
                <x-form.input model="form.slug" :label="__('Web address')" :hint="__('The last part of the page\'s address. Leave it as it is unless the link must change.')" :mono="true" dir="ltr" />
            @endunless
        </x-admin.form-section>

        <x-admin.form-section id="images" :title="__('Images')" :form="false">
            <x-admin.image-grid :label="__('Images')" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" multiple :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
            @if ($isNew)
                <p class="text-sm font-normal text-ink-muted">{{ __('Images can be added once the item has a title and has been saved once.') }}</p>
            @endif
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
