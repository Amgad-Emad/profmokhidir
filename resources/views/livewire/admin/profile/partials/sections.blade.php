{{--
    The profile's sections (identity, biography, expertise and key facts, portrait, contact): on the
    profile's own screen, and folded inside Admin › Pages › About, which saves them with the page.
    Expects $collapsed and the profile form's view data.
--}}
@use('App\Constants\Locales')
<x-admin.form-section id="identity" :collapsed="$collapsed" :title="__('Identity')" :intro="__('How he is named at the top of the About page and in search results.')">
    <x-admin.locale-tabs id="identity-text" :label="__('Identity')" :fields="['name', 'title', 'affiliation']">
        @foreach (Locales::all() as $locale)
            @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
            <x-admin.locale-panel tabs="identity-text" :locale="$locale">
                <x-form.input :model="$field.'name'" :label="__('Name')" :required="$locale === Locales::DEFAULT" :hint="__('His full name, as it should appear on the site.')" />
                <div class="form-row">
                    <x-form.input class="form-col--half" :model="$field.'title'" :label="__('Title')" :hint="__('For example Professor of Agronomy.')" />
                    <x-form.input class="form-col--half" :model="$field.'affiliation'" :label="__('Affiliation')" :hint="__('The university or institution he is known for.')" />
                </div>
            </x-admin.locale-panel>
        @endforeach
    </x-admin.locale-tabs>
</x-admin.form-section>

<x-admin.form-section id="biography" :collapsed="$collapsed" :title="__('Biography')">
    <x-admin.locale-tabs id="biography-text" :label="__('Biography')" :fields="['short_bio', 'full_bio']">
        @foreach (Locales::all() as $locale)
            @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
            <x-admin.locale-panel tabs="biography-text" :locale="$locale">
                <x-form.textarea :model="$field.'short_bio'" :label="__('Short biography')" rows="3" :hint="__('Two or three sentences. They open the home page and appear when the site is shared.')" />
                <x-form.editor :model="$field.'full_bio'" :label="__('Full biography')" :placeholder="__('Start writing…')" :hint="__('The life story on the About page. Use the toolbar for headings, bold, italic, links, lists and quotes.')" />
            </x-admin.locale-panel>
        @endforeach
    </x-admin.locale-tabs>
</x-admin.form-section>

<x-admin.form-section id="expertise" :collapsed="$collapsed" :title="__('Expertise & key facts')">
    <x-admin.locale-tabs id="expertise-text" :label="__('Expertise & key facts')" :fields="['expertise', 'key_facts']">
        @foreach (Locales::all() as $locale)
            @php
                $isDefault = $locale === Locales::DEFAULT;
                $field = $isDefault ? 'form.' : 'form.translations.'.$locale.'.';
                $facts = $isDefault ? ($form['key_facts'] ?? []) : ($form['translations'][$locale]['key_facts'] ?? []);
                $localeArgument = $isDefault ? '' : ", '".$locale."'";
            @endphp
            <x-admin.locale-panel tabs="expertise-text" :locale="$locale">
                <x-form.tags :model="$field.'expertise'" :label="__('Expertise')" :hint="__('Each field of work as a short phrase, in the order they should appear. Press Enter after each one.')" :data-expertise="$locale" />

                <fieldset class="form-fieldset" data-key-facts="{{ $locale }}">
                    <legend class="t-label form-label">{{ __('Key facts') }}<span class="form-requirement"><span aria-hidden="true">{{ trim(\App\Constants\FormControls::HINT_SEPARATOR) }}</span> {{ __(\App\Constants\FormControls::OPTIONAL) }}</span></legend>
                    <p class="form-hint">{{ __('A short label and its value, such as Born and 1940. They are listed beside the biography.') }}</p>
                    <div class="mt-4 form-grid">
                        @foreach ($facts as $index => $fact)
                            <div class="form-row" wire:key="fact-{{ $locale }}-{{ $index }}">
                                <x-form.input class="form-col--third" :model="$field.'key_facts.'.$index.'.label'" :label="__('Fact :number label', ['number' => $index + 1])" :optional="false" />
                                <x-form.input class="form-col--half" :model="$field.'key_facts.'.$index.'.value'" :label="__('Fact :number value', ['number' => $index + 1])" :optional="false" />
                                <x-button variant="danger" size="sm" type="button" class="form-col--action" wire:click="removeKeyFact({{ $index }}{{ $localeArgument }})">{{ __('Remove') }}<span class="sr-only"> {{ __('key fact :number', ['number' => $index + 1]) }}</span></x-button>
                            </div>
                        @endforeach
                    </div>
                    <x-button variant="quiet" size="sm" type="button" class="mt-4" wire:click="addKeyFact({{ ltrim($localeArgument, ', ') }})"><x-icon name="plus" class="size-4" />{{ __('Add a key fact') }}</x-button>
                </fieldset>
            </x-admin.locale-panel>
        @endforeach
    </x-admin.locale-tabs>

    <div class="form-row">
        <x-form.input model="form.students_supervised" :label="__('Students supervised')" type="number" inputmode="numeric" min="0" class="form-col--third" :hint="__('Only if the family or university has confirmed the number. It appears in the figures on the home page; leave it blank to leave it out.')" />
    </div>
</x-admin.form-section>

<x-admin.form-section id="portrait" :collapsed="$collapsed" :title="__('Portrait photo')" :intro="__('The photograph that opens the About page and the sign-in screens. It needs alt text, a caption, a place and a year before the profile can be published.')" :form="false">
    <x-admin.image-grid :label="__('Portrait photo')" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
    @if ($isNew)
        <p class="text-sm font-normal text-ink-muted">{{ __('The portrait can be added once the profile has a name and has been saved once.') }}</p>
    @endif
</x-admin.form-section>

<x-admin.form-section id="contact" :collapsed="$collapsed" :title="__('Contact')" :intro="__('Shown at the foot of the About page, only what is filled in here. Empty a field to take it off the page.')">
    <div class="form-row">
        <x-form.input class="form-col--half" model="form.contact_email_public" :label="__('Public contact email')" type="email" dir="ltr" :hint="__('An address the family is happy to publish.')" />
        <x-form.input class="form-col--half" model="form.contact_phone" :label="__('Public contact phone')" type="tel" dir="ltr" :hint="__('With the country code, for example +249 …')" />
    </div>
    <x-admin.locale-tabs id="contact-text" :label="__('Contact')" :fields="['contact_address', 'contact_note']">
        @foreach (Locales::all() as $locale)
            @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
            <x-admin.locale-panel tabs="contact-text" :locale="$locale">
                <x-form.textarea :model="$field.'contact_address'" :label="__('Postal address')" rows="3" :hint="__('One line per line of the address.')" />
                <x-form.input :model="$field.'contact_note'" :label="__('Contact note')" :hint="__('For example Enquiries are answered by the family.')" />
            </x-admin.locale-panel>
        @endforeach
    </x-admin.locale-tabs>
</x-admin.form-section>
