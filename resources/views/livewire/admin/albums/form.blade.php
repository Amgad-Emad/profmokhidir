@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Media')" :title="$isNew ? __('New album') : __('Edit album')" :intro="__('A group of photographs on the Pictures page. Every photo needs alt text and a caption with place and year before the album can be published.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['album' => __('The album'), 'photos' => __('Photos')]" class="mt-10">
        <x-admin.form-section id="album" :title="__('The album')">
            <x-admin.locale-tabs id="album-text" :label="__('The album')" :fields="['title', 'description']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="album-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('For example Field trials in Gezira, 1972.')" />
                        <x-form.textarea :model="$field.'description'" :label="__('Description')" rows="3" :hint="__('A sentence or two shown above the photos.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            @unless ($isNew)
                <x-form.input model="form.slug" :label="__('Web address')" :hint="__('The last part of the album\'s address. Leave it as it is unless the link must change.')" :mono="true" dir="ltr" />
            @endunless
        </x-admin.form-section>

        <x-admin.form-section id="photos" :title="__('Photos')" :form="false">
            <x-admin.image-grid :label="__('Photos')" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" multiple orderable :cover-id="$coverId" :confirming-id="$confirmingImageId" :can-upload="! $isNew" :archive="$archive" :decades="$decades" />
            @if ($isNew)
                <p class="text-sm font-normal text-ink-muted">{{ __('Photos can be added once the album has a title and has been saved once.') }}</p>
            @endif
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
