{{-- The brand's image details (alt text, caption, place, year, treatment, credit, permissions note) bound under `prefix` (e.g. details). --}}
@props(['prefix', 'required' => false])
@use('App\Constants\MediaProperties')
@use('App\Constants\MediaTreatment')
<div class="grid gap-5 sm:grid-cols-2" data-image-details>
    <x-admin.field :name="$prefix.'.'.MediaProperties::ALT_TEXT" label="Alt text" :required="$required" hint="What the image shows, for people who cannot see it." class="sm:col-span-2" />
    <x-admin.field :name="$prefix.'.'.MediaProperties::CAPTION" label="Caption" :required="$required" class="sm:col-span-2" />
    <x-admin.field :name="$prefix.'.'.MediaProperties::PLACE" label="Place" :required="$required" />
    <x-admin.field :name="$prefix.'.'.MediaProperties::YEAR" label="Year" type="number" :required="$required" inputmode="numeric" />
    <x-admin.select-field :name="$prefix.'.'.MediaProperties::TREATMENT" label="Treatment" :options="MediaTreatment::labels()" required hint="Portraits go warm monochrome; archive photos take the evergreen duotone." class="sm:col-span-2" />
    <x-admin.field :name="$prefix.'.'.MediaProperties::CREDIT" label="Credit" />
    <x-admin.field :name="$prefix.'.'.MediaProperties::PERMISSIONS_NOTE" label="Permissions note" />
</div>
