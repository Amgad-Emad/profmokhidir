@use('App\Constants\SettingKeys')
<div>
    <x-admin.page-header eyebrow="Settings" title="Site settings" intro="Public contact details, where staff notifications go and how emails are signed. Leave a field blank to use the default." />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-12" novalidate data-site-settings>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <fieldset class="form-grid">
            <legend class="t-label text-accent-text">Public contact details</legend>
            <p class="text-sm font-light text-ink-muted">Shown in the Contact block of the About page when the profile has no contact email of its own.</p>
            <div class="form-row">
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CONTACT_EMAIL" :label="$labels[SettingKeys::CONTACT_EMAIL]" type="email" />
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CONTACT_PHONE" :label="$labels[SettingKeys::CONTACT_PHONE]" type="tel" hint="Digits, spaces and a leading + only." />
            </div>
            <x-form.textarea :model="'form.'.SettingKeys::CONTACT_ADDRESS" :label="$labels[SettingKeys::CONTACT_ADDRESS]" rows="3" />
        </fieldset>

        <fieldset class="form-grid">
            <legend class="t-label text-accent-text">Notifications</legend>
            <p class="text-sm font-light text-ink-muted">New tributes and community applications are announced to this address. When it is blank, every administrator and editor is emailed instead.</p>
            <x-form.input :model="'form.'.SettingKeys::NOTIFICATION_EMAIL" :label="$labels[SettingKeys::NOTIFICATION_EMAIL]" type="email" />
        </fieldset>

        <fieldset class="form-grid">
            <legend class="t-label text-accent-text">Mail sender</legend>
            <p class="text-sm font-light text-ink-muted">The name and address every email from the site is sent from. The address should belong to the site's own domain so that it is not rejected as spam.
                @if ($defaults[SettingKeys::MAIL_FROM_ADDRESS])
                    Default: <span class="font-mono text-ink">{{ $defaults[SettingKeys::MAIL_FROM_NAME] }} &lt;{{ $defaults[SettingKeys::MAIL_FROM_ADDRESS] }}&gt;</span>.
                @endif
            </p>
            <div class="form-row">
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::MAIL_FROM_NAME" :label="$labels[SettingKeys::MAIL_FROM_NAME]" />
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::MAIL_FROM_ADDRESS" :label="$labels[SettingKeys::MAIL_FROM_ADDRESS]" type="email" />
            </div>
        </fieldset>

        <fieldset class="form-grid" data-chat-upload-settings>
            <legend class="t-label text-accent-text">Community chat uploads</legend>
            <p class="text-sm font-light text-ink-muted">Approved members may attach up to {{ \App\Constants\ChatAttachments::MAX_FILES }} images or documents to a message. Files are private to members and administrators. Leave a limit blank for the default ({{ \App\Constants\ChatAttachments::IMAGE_MAX_MB }} MB for images, {{ \App\Constants\ChatAttachments::DOCUMENT_MAX_MB }} MB for documents).</p>
            <x-form.toggle :model="'form.'.SettingKeys::CHAT_UPLOADS_ENABLED" :label="$labels[SettingKeys::CHAT_UPLOADS_ENABLED]" hint="When off, the paperclip is hidden and new uploads are refused; files already sent stay available." />
            <div class="form-row">
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CHAT_IMAGE_MAX_MB" :label="$labels[SettingKeys::CHAT_IMAGE_MAX_MB]" type="number" inputmode="numeric" :min="\App\Constants\ChatAttachments::LIMIT_MIN_MB" :max="\App\Constants\ChatAttachments::LIMIT_MAX_MB" />
                <x-form.input class="form-col--half" :model="'form.'.SettingKeys::CHAT_DOCUMENT_MAX_MB" :label="$labels[SettingKeys::CHAT_DOCUMENT_MAX_MB]" type="number" inputmode="numeric" :min="\App\Constants\ChatAttachments::LIMIT_MIN_MB" :max="\App\Constants\ChatAttachments::LIMIT_MAX_MB" />
            </div>
        </fieldset>

        <fieldset class="form-grid" id="analytics" data-analytics-settings>
            <legend class="t-label text-accent-text">Visitor analytics</legend>
            <div class="flex flex-col gap-3 text-sm font-light text-ink-muted">
                <p>The site's own count of its readers, shown under Audience on the dashboard. Switch it on only once the client has approved visitor analytics and any notice it needs. While it is on, the footer links to a Privacy page that explains it to visitors.</p>
                <p>What is recorded: the page, how long it was read (tab in view and the visitor active within {{ \App\Constants\Analytics::IDLE_SECONDS }} seconds), how far it was scrolled, the referring site's name, the country, the device type and browser family, and a campaign source when a link carries one.</p>
                <p>What is not: no cookies, no IP addresses, no user agents, nothing that names a person. Visitors are told apart within one day by a hash whose salt is replaced every day. Signed-in staff, bots, and browsers that send Do Not Track or Global Privacy Control are never counted. Page views are deleted after {{ \App\Constants\Analytics::RAW_RETENTION_DAYS }} days and daily totals after {{ \App\Constants\Analytics::ROLLUP_RETENTION_MONTHS }} months.</p>
            </div>
            <x-form.toggle :model="'form.'.SettingKeys::ANALYTICS_ENABLED" :label="$labels[SettingKeys::ANALYTICS_ENABLED]" hint="Off by default. Turning it off stops recording at once; figures already collected stay on the report." />
        </fieldset>

        <div class="flex flex-wrap items-center gap-4 border-t border-rule pt-8">
            <x-button type="submit" wire:loading.attr="disabled" data-save>Save settings</x-button>
            <x-button type="button" variant="quiet" :href="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)">Back to the dashboard</x-button>
            <span class="text-sm font-light text-ink-muted" wire:loading wire:target="save">Saving…</span>
        </div>
    </form>
</div>
