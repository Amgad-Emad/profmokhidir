@use('App\Constants\SettingKeys')
<div>
    <x-admin.page-header eyebrow="Settings" title="Site settings" intro="Public contact details, where staff notifications go and how emails are signed. Leave a field blank to use the default." />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-12" novalidate data-site-settings>
        <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
        <fieldset class="space-y-6">
            <legend class="t-label text-accent-text">Public contact details</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">Shown in the Contact block of the About page when the profile has no contact email of its own.</p>
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input :model="'form.'.SettingKeys::CONTACT_EMAIL" :label="$labels[SettingKeys::CONTACT_EMAIL]" type="email" />
                <x-form.input :model="'form.'.SettingKeys::CONTACT_PHONE" :label="$labels[SettingKeys::CONTACT_PHONE]" type="tel" hint="Digits, spaces and a leading + only." />
            </div>
            <x-form.textarea :model="'form.'.SettingKeys::CONTACT_ADDRESS" :label="$labels[SettingKeys::CONTACT_ADDRESS]" rows="3" />
        </fieldset>

        <fieldset class="space-y-6">
            <legend class="t-label text-accent-text">Notifications</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">New tributes and community applications are announced to this address. When it is blank, every administrator and editor is emailed instead.</p>
            <x-form.input :model="'form.'.SettingKeys::NOTIFICATION_EMAIL" :label="$labels[SettingKeys::NOTIFICATION_EMAIL]" type="email" />
        </fieldset>

        <fieldset class="space-y-6">
            <legend class="t-label text-accent-text">Mail sender</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">The name and address every email from the site is sent from. The address should belong to the site's own domain so that it is not rejected as spam.
                @if ($defaults[SettingKeys::MAIL_FROM_ADDRESS])
                    Default: <span class="font-mono text-ink">{{ $defaults[SettingKeys::MAIL_FROM_NAME] }} &lt;{{ $defaults[SettingKeys::MAIL_FROM_ADDRESS] }}&gt;</span>.
                @endif
            </p>
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input :model="'form.'.SettingKeys::MAIL_FROM_NAME" :label="$labels[SettingKeys::MAIL_FROM_NAME]" />
                <x-form.input :model="'form.'.SettingKeys::MAIL_FROM_ADDRESS" :label="$labels[SettingKeys::MAIL_FROM_ADDRESS]" type="email" />
            </div>
        </fieldset>

        <fieldset class="space-y-6" data-chat-upload-settings>
            <legend class="t-label text-accent-text">Community chat uploads</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">Approved members may attach up to {{ \App\Constants\ChatAttachments::MAX_FILES }} images or documents to a message. Files are private to members and administrators. Leave a limit blank for the default ({{ \App\Constants\ChatAttachments::IMAGE_MAX_MB }} MB for images, {{ \App\Constants\ChatAttachments::DOCUMENT_MAX_MB }} MB for documents).</p>
            <x-form.toggle :model="'form.'.SettingKeys::CHAT_UPLOADS_ENABLED" :label="$labels[SettingKeys::CHAT_UPLOADS_ENABLED]" hint="When off, the paperclip is hidden and new uploads are refused; files already sent stay available." />
            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.input :model="'form.'.SettingKeys::CHAT_IMAGE_MAX_MB" :label="$labels[SettingKeys::CHAT_IMAGE_MAX_MB]" type="number" inputmode="numeric" :min="\App\Constants\ChatAttachments::LIMIT_MIN_MB" :max="\App\Constants\ChatAttachments::LIMIT_MAX_MB" />
                <x-form.input :model="'form.'.SettingKeys::CHAT_DOCUMENT_MAX_MB" :label="$labels[SettingKeys::CHAT_DOCUMENT_MAX_MB]" type="number" inputmode="numeric" :min="\App\Constants\ChatAttachments::LIMIT_MIN_MB" :max="\App\Constants\ChatAttachments::LIMIT_MAX_MB" />
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-4 border-t border-rule pt-8">
            <x-button type="submit" wire:loading.attr="disabled" data-save>Save settings</x-button>
            <x-button type="button" variant="quiet" :href="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)">Back to the dashboard</x-button>
            <span class="text-sm font-light text-ink-muted" wire:loading wire:target="save">Saving…</span>
        </div>
    </form>
</div>
