{{-- Alone it is the profile's own screen; embedded, only its sections, inside Admin › Pages › About, which saves them. --}}
<div @if ($embedded) data-profile-embedded @endif>
    @if ($embedded)
        @include('livewire.admin.profile.partials.sections', ['collapsed' => true])
    @else
        <x-admin.page-header :eyebrow="__('Content')" :title="__('About & profile')" :intro="__('Everything on the About page: who he was, his biography, his fields of work, his portrait and how visitors can get in touch.')" />
        <x-admin.flash />

        <x-admin.edit-layout :editor="$editor" :sections="['identity' => __('Identity'), 'biography' => __('Biography'), 'expertise' => __('Expertise & key facts'), 'portrait' => __('Portrait photo'), 'contact' => __('Contact')]" class="mt-10">
            @include('livewire.admin.profile.partials.sections', ['collapsed' => false])
        </x-admin.edit-layout>

        <x-admin.revisions :editor="$editor" :show="$showHistory" />
    @endif
</div>
