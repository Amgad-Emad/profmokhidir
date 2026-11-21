<?php

namespace Database\Seeders;

use App\Constants\ChatAttachments;
use App\Constants\Locales;
use App\Constants\SettingKeys;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

/**
 * Placeholder site settings for local development. Existing values are left
 * untouched, so re-seeding never overwrites what an admin has entered.
 */
class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(SettingService $settings): void
    {
        $defaults = [
            SettingKeys::CONTACT_EMAIL => 'contact@example.com',
            SettingKeys::CONTACT_PHONE => null,
            SettingKeys::CONTACT_ADDRESS => '[Placeholder] Faculty of Agriculture, University of Khartoum, Sudan',
            SettingKeys::NOTIFICATION_EMAIL => 'admin@example.com',
            SettingKeys::MAIL_FROM_NAME => (string) config('app.name'),
            SettingKeys::MAIL_FROM_ADDRESS => (string) config('mail.from.address'),
            SettingKeys::CHAT_UPLOADS_ENABLED => '1',
            SettingKeys::CHAT_IMAGE_MAX_MB => (string) ChatAttachments::IMAGE_MAX_MB,
            SettingKeys::CHAT_DOCUMENT_MAX_MB => (string) ChatAttachments::DOCUMENT_MAX_MB,
            // Off: visitor analytics waits for the client's approval.
            SettingKeys::ANALYTICS_ENABLED => '0',
            SettingKeys::ARABIC_DIGITS => Locales::DIGITS_LATIN,
            // Every section shows.
            SettingKeys::HIDDEN_SECTIONS => null,
        ];

        $settings->seedDefaults($defaults);
    }
}
