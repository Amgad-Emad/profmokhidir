@use('App\Constants\SettingKeys')
<div>
    <x-admin.page-header eyebrow="Settings" title="Site settings" intro="Public contact details, where staff notifications go and how emails are signed. Leave a field blank to use the default." />
    <div class="mt-8"><x-admin.flash /></div>

    <form wire:submit="save" class="mt-4 max-w-3xl space-y-12" novalidate data-site-settings>
        <fieldset class="space-y-6">
            <legend class="t-label text-accent-text">Public contact details</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">Shown in the Contact block of the About page when the profile has no contact email of its own.</p>
            <div class="grid gap-6 sm:grid-cols-2">
                <x-admin.field :name="'form.'.SettingKeys::CONTACT_EMAIL" :label="$labels[SettingKeys::CONTACT_EMAIL]" type="email" />
                <x-admin.field :name="'form.'.SettingKeys::CONTACT_PHONE" :label="$labels[SettingKeys::CONTACT_PHONE]" type="tel" hint="Digits, spaces and a leading + only." />
            </div>
            <x-admin.textarea-field :name="'form.'.SettingKeys::CONTACT_ADDRESS" :label="$labels[SettingKeys::CONTACT_ADDRESS]" rows="3" />
        </fieldset>

        <fieldset class="space-y-6">
            <legend class="t-label text-accent-text">Notifications</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">New tributes and community applications are announced to this address. When it is blank, every administrator and editor is emailed instead.</p>
            <x-admin.field :name="'form.'.SettingKeys::NOTIFICATION_EMAIL" :label="$labels[SettingKeys::NOTIFICATION_EMAIL]" type="email" />
        </fieldset>

        <fieldset class="space-y-6">
            <legend class="t-label text-accent-text">Mail sender</legend>
            <p class="mt-2 text-sm font-light text-ink-muted">The name and address every email from the site is sent from. The address should belong to the site's own domain so that it is not rejected as spam.
                @if ($defaults[SettingKeys::MAIL_FROM_ADDRESS])
                    Default: <span class="font-mono text-ink">{{ $defaults[SettingKeys::MAIL_FROM_NAME] }} &lt;{{ $defaults[SettingKeys::MAIL_FROM_ADDRESS] }}&gt;</span>.
                @endif
            </p>
            <div class="grid gap-6 sm:grid-cols-2">
                <x-admin.field :name="'form.'.SettingKeys::MAIL_FROM_NAME" :label="$labels[SettingKeys::MAIL_FROM_NAME]" />
                <x-admin.field :name="'form.'.SettingKeys::MAIL_FROM_ADDRESS" :label="$labels[SettingKeys::MAIL_FROM_ADDRESS]" type="email" />
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-4 border-t border-rule pt-8">
            <x-button type="submit" wire:loading.attr="disabled" data-save>Save settings</x-button>
            <x-button type="button" variant="outline" :href="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)">Back to the dashboard</x-button>
            <span class="text-sm font-light text-ink-muted" wire:loading wire:target="save">Saving…</span>
        </div>
    </form>
</div>
