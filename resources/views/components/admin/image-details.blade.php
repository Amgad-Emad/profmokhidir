{{--
    The brand's image details bound under `prefix` (e.g. details), on the form grid: alt text,
    caption and place in English and Arabic (tabs; the Arabic ones are optional and stored under
    MediaProperties::translationPath()), then the year, treatment, credit and permissions note,
    which are the same in every language.
--}}
@props(['prefix', 'required' => false])
@use('App\Constants\Locales')
@use('App\Constants\MediaProperties')
@php
    $tabs = 'image-'.str_replace('.', '-', $prefix);
    $described = [MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE];
    $errorKeys = [];

    foreach (Locales::all() as $locale) {
        $errorKeys[$locale] = array_map(fn (string $property): string => $prefix.'.'.($locale === Locales::DEFAULT ? $property : MediaProperties::translationPath($locale, $property)), $described);
    }
@endphp
<div class="form-grid" data-image-details>
    <x-admin.locale-tabs :id="$tabs" :label="__('Image details')" :error-keys="$errorKeys">
        @foreach (Locales::all() as $locale)
            @php
                $isDefault = $locale === Locales::DEFAULT;
                $field = fn (string $property): string => $prefix.'.'.($isDefault ? $property : MediaProperties::translationPath($locale, $property));
            @endphp
            <x-admin.locale-panel :tabs="$tabs" :locale="$locale">
                <x-form.input :model="$field(MediaProperties::ALT_TEXT)" :label="__('Alt text')" :required="$required && $isDefault" :hint="__(\App\Constants\FormControls::UPLOAD_ALT_PROMPT)" />
                <x-form.input :model="$field(MediaProperties::CAPTION)" :label="__('Caption')" :required="$required && $isDefault" />
                <x-form.input :model="$field(MediaProperties::PLACE)" :label="__('Place')" :required="$required && $isDefault" />
            </x-admin.locale-panel>
        @endforeach
    </x-admin.locale-tabs>
    <div class="form-row">
        <x-form.year :model="$prefix.'.'.MediaProperties::YEAR" :label="__('Year')" :required="$required" class="form-col--third" />
    </div>
    <div class="form-row">
        <x-form.input :model="$prefix.'.'.MediaProperties::CREDIT" :label="__('Credit')" class="form-col--half" />
        <x-form.input :model="$prefix.'.'.MediaProperties::PERMISSIONS_NOTE" :label="__('Permissions note')" class="form-col--half" />
    </div>
</div>
