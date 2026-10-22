{{-- The brand's image details (alt text, caption, place, year, treatment, credit, permissions note) bound under `prefix` (e.g. details). --}}
@props(['prefix', 'required' => false])
@use('App\Constants\MediaProperties')
@use('App\Constants\MediaTreatment')
<div class="grid gap-6 sm:grid-cols-2" data-image-details>
    <x-form.input :model="$prefix.'.'.MediaProperties::ALT_TEXT" label="Alt text" :required="$required" :hint="\App\Constants\FormControls::UPLOAD_ALT_PROMPT" class="sm:col-span-2" />
    <x-form.input :model="$prefix.'.'.MediaProperties::CAPTION" label="Caption" :required="$required" class="sm:col-span-2" />
    <x-form.input :model="$prefix.'.'.MediaProperties::PLACE" label="Place" :required="$required" />
    <x-form.year :model="$prefix.'.'.MediaProperties::YEAR" label="Year" :required="$required" />
    <x-form.select :model="$prefix.'.'.MediaProperties::TREATMENT" label="Treatment" :options="MediaTreatment::labels()" required hint="Portraits go warm monochrome; archive photos take the evergreen duotone." class="sm:col-span-2" />
    <x-form.input :model="$prefix.'.'.MediaProperties::CREDIT" label="Credit" />
    <x-form.input :model="$prefix.'.'.MediaProperties::PERMISSIONS_NOTE" label="Permissions note" />
</div>
