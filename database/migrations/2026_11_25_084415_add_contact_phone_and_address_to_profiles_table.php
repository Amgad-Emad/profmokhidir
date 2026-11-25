<?php

use App\Constants\Locales;
use App\Constants\SettingKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The About page's contact details become the page's own (Admin › Pages › About):
 * a phone and a postal address on the profile, beside its email and note, instead
 * of the site-wide ones from Site settings. The site values the About page showed
 * until now are copied in, so nothing disappears from the page.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('contact_phone', 40)->nullable()->after('contact_email_public');
            // English and Arabic, like the other words of the profile.
            $table->json('contact_address')->nullable()->after('contact_phone');
        });

        $site = DB::table('settings')
            ->whereIn('key', [SettingKeys::CONTACT_EMAIL, SettingKeys::CONTACT_PHONE, SettingKeys::CONTACT_ADDRESS])
            ->pluck('value', 'key')
            ->map(fn (?string $value): ?string => $value === null || trim($value) === '' ? null : trim($value));

        foreach (DB::table('profiles')->get(['id', 'contact_email_public']) as $profile) {
            $address = $site[SettingKeys::CONTACT_ADDRESS] ?? null;

            DB::table('profiles')->where('id', $profile->id)->update([
                'contact_email_public' => $profile->contact_email_public ?? ($site[SettingKeys::CONTACT_EMAIL] ?? null),
                'contact_phone' => $site[SettingKeys::CONTACT_PHONE] ?? null,
                'contact_address' => $address === null ? null : json_encode([Locales::DEFAULT => $address], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['contact_phone', 'contact_address']);
        });
    }
};
