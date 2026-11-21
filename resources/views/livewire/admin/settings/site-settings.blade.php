@use('App\Constants\SettingKeys')
<div>
    <x-admin.page-header :eyebrow="__('Settings')" :title="__('Site settings')" :intro="__('Public contact details, where staff notifications go and how emails are signed. Leave a field blank to use the default.')" />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-12" novalidate data-site-settings>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <fieldset class="form-grid" id="sections" data-section-visibility>
            <legend class="t-label text-accent-text">{{ __('Sections on the site') }}</legend>
            <p class="text-sm font-normal text-ink-muted">{{ __('Switch a section off to hide it from visitors: it leaves the menus, the home page, search and the sitemap, and its pages answer "not found". Its content is kept, and staff can still open it to check it.') }}</p>
            <div class="form-row">
                @foreach ($sections as $key => $label)
                    <x-form.toggle class="form-col--half" :model="'shown.'.$key" :label="$label" />
                @endforeach
            </div>
        </fieldset>

        <fieldset class="form-grid">
            <legend class="t-label text-accent-text">{{ __('Public contact details') }}</legend>
            <p class="text-sm font-normal text-ink-muted">{{ __('Shown in the Contact block of the About page when the profile has no contact email of its own.') }}</p>
            <div class="form-row">
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CONTACT_EMAIL" :label="$labels[SettingKeys::CONTACT_EMAIL]" type="email" />
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CONTACT_PHONE" :label="$labels[SettingKeys::CONTACT_PHONE]" type="tel" :hint="__('Digits, spaces and a leading + only.')" />
            </div>
            <x-form.textarea :model="'form.'.SettingKeys::CONTACT_ADDRESS" :label="$labels[SettingKeys::CONTACT_ADDRESS]" rows="3" />
        </fieldset>

        <fieldset class="form-grid">
            <legend class="t-label text-accent-text">{{ __('Notifications') }}</legend>
            <p class="text-sm font-normal text-ink-muted">{{ __('New tributes and community applications are announced to this address. When it is blank, every administrator and editor is emailed instead.') }}</p>
            <x-form.input :model="'form.'.SettingKeys::NOTIFICATION_EMAIL" :label="$labels[SettingKeys::NOTIFICATION_EMAIL]" type="email" />
        </fieldset>

        <fieldset class="form-grid">
            <legend class="t-label text-accent-text">{{ __('Mail sender') }}</legend>
            <p class="text-sm font-normal text-ink-muted">{{ __('The name and address every email from the site is sent from. The address should belong to the site\'s own domain so that it is not rejected as spam.') }}
                @if ($defaults[SettingKeys::MAIL_FROM_ADDRESS])
                    {{ __('Default:') }} <span class="font-mono text-ink" dir="ltr">{{ $defaults[SettingKeys::MAIL_FROM_NAME] }} &lt;{{ $defaults[SettingKeys::MAIL_FROM_ADDRESS] }}&gt;</span>.
                @endif
            </p>
            <div class="form-row">
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::MAIL_FROM_NAME" :label="$labels[SettingKeys::MAIL_FROM_NAME]" />
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::MAIL_FROM_ADDRESS" :label="$labels[SettingKeys::MAIL_FROM_ADDRESS]" type="email" />
            </div>
        </fieldset>

        <fieldset class="form-grid" data-chat-upload-settings>
            <legend class="t-label text-accent-text">{{ __('Community chat uploads') }}</legend>
            <p class="text-sm font-normal text-ink-muted">{{ __('Approved members may attach up to :files images or documents to a message. Files are private to members and administrators. Leave a limit blank for the default (:image MB for images, :document MB for documents).', ['files' => app(\App\Services\LocaleService::class)->digits(\App\Constants\ChatAttachments::MAX_FILES), 'image' => app(\App\Services\LocaleService::class)->digits(\App\Constants\ChatAttachments::IMAGE_MAX_MB), 'document' => app(\App\Services\LocaleService::class)->digits(\App\Constants\ChatAttachments::DOCUMENT_MAX_MB)]) }}</p>
            <x-form.toggle :model="'form.'.SettingKeys::CHAT_UPLOADS_ENABLED" :label="$labels[SettingKeys::CHAT_UPLOADS_ENABLED]" :hint="__('When off, the paperclip is hidden and new uploads are refused; files already sent stay available.')" />
            <div class="form-row">
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CHAT_IMAGE_MAX_MB" :label="$labels[SettingKeys::CHAT_IMAGE_MAX_MB]" type="number" inputmode="numeric" :min="\App\Constants\ChatAttachments::LIMIT_MIN_MB" :max="\App\Constants\ChatAttachments::LIMIT_MAX_MB" />
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CHAT_DOCUMENT_MAX_MB" :label="$labels[SettingKeys::CHAT_DOCUMENT_MAX_MB]" type="number" inputmode="numeric" :min="\App\Constants\ChatAttachments::LIMIT_MIN_MB" :max="\App\Constants\ChatAttachments::LIMIT_MAX_MB" />
            </div>
        </fieldset>

        <fieldset class="form-grid" id="analytics" data-analytics-settings>
            <legend class="t-label text-accent-text">{{ __('Visitor analytics') }}</legend>
            <div class="flex flex-col gap-3 text-sm font-normal text-ink-muted">
                <p>{{ __('The site\'s own count of its readers, shown under Audience on the dashboard. Switch it on only once the client has approved visitor analytics and any notice it needs. While it is on, the footer links to a Privacy page that explains it to visitors.') }}</p>
                <p>{{ __('What is recorded: the page, how long it was read (tab in view and the visitor active within :seconds seconds), how far it was scrolled, the referring site\'s name, the country, the device type and browser family, and a campaign source when a link carries one.', ['seconds' => app(\App\Services\LocaleService::class)->digits(\App\Constants\Analytics::IDLE_SECONDS)]) }}</p>
                <p>{{ __('What is not: no cookies, no IP addresses, no user agents, nothing that names a person. Visitors are told apart within one day by a hash whose salt is replaced every day. Signed-in staff, bots, and browsers that send Do Not Track or Global Privacy Control are never counted. Page views are deleted after :days days and daily totals after :months months.', ['days' => app(\App\Services\LocaleService::class)->digits(\App\Constants\Analytics::RAW_RETENTION_DAYS), 'months' => app(\App\Services\LocaleService::class)->digits(\App\Constants\Analytics::ROLLUP_RETENTION_MONTHS)]) }}</p>
            </div>
            <x-form.toggle :model="'form.'.SettingKeys::ANALYTICS_ENABLED" :label="$labels[SettingKeys::ANALYTICS_ENABLED]" :hint="__('Off by default. Turning it off stops recording at once; figures already collected stay on the report.')" />
        </fieldset>

        <fieldset class="form-grid" id="arabic-digits" data-arabic-digits-settings>
            <legend class="t-label text-accent-text">{{ __('Arabic pages') }}</legend>
            <x-form.radio-group :model="'form.'.SettingKeys::ARABIC_DIGITS" :label="$labels[SettingKeys::ARABIC_DIGITS]" :options="[
                \App\Constants\Locales::DIGITS_LATIN => __('Western (0–9)'),
                \App\Constants\Locales::DIGITS_ARABIC => __('Arabic-Indic (٠–٩)'),
            ]" :hint="__('Applies to counts and numbers in running text on Arabic pages. Years, citations, DOIs and data always stay in Western digits.')" />
        </fieldset>

        <div class="flex flex-wrap items-center gap-4 border-t border-rule pt-8">
            <x-button type="submit" wire:loading.attr="disabled" data-save>{{ __('Save settings') }}</x-button>
            <x-button type="button" variant="quiet" :href="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)">{{ __('Back to the dashboard') }}</x-button>
            <span class="text-sm font-normal text-ink-muted" wire:loading wire:target="save">{{ __('Saving…') }}</span>
        </div>
    </form>
</div>
