{{-- The brand's image details (alt text, caption, place, year, treatment, credit, permissions note) bound under `prefix` (e.g. details), on the form grid. --}}
@props(['prefix', 'required' => false])
@use('App\Constants\MediaProperties')
@use('App\Constants\MediaTreatment')
<div class="form-grid" data-image-details>
    <x-form.input :model="$prefix.'.'.MediaProperties::ALT_TEXT" label="Alt text" :required="$required" :hint="\App\Constants\FormControls::UPLOAD_ALT_PROMPT" />
    <x-form.input :model="$prefix.'.'.MediaProperties::CAPTION" label="Caption" :required="$required" />
    <div class="form-row">
        <x-form.input :model="$prefix.'.'.MediaProperties::PLACE" label="Place" :required="$required" class="form-col--two-thirds" />
        <x-form.year :model="$prefix.'.'.MediaProperties::YEAR" label="Year" :required="$required" class="form-col--third" />
    </div>
    <x-form.select :model="$prefix.'.'.MediaProperties::TREATMENT" label="Treatment" :options="MediaTreatment::labels()" required hint="Portraits go warm monochrome; archive photos take the evergreen duotone." />
    <div class="form-row">
        <x-form.input :model="$prefix.'.'.MediaProperties::CREDIT" label="Credit" class="form-col--half" />
        <x-form.input :model="$prefix.'.'.MediaProperties::PERMISSIONS_NOTE" label="Permissions note" class="form-col--half" />
    </div>
</div>
