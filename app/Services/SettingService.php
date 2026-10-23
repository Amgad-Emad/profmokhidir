<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\ChatAttachments;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;

/**
 * Site settings: the public contact details, the address that receives staff
 * notifications (tributes awaiting review, new community applications) and
 * the mail sender, and whether (and how large) members may attach files in the
 * community chat. Values are cached; saving flushes the cache, writes the
 * audit log and re-applies the mail sender to the running configuration.
 */
class SettingService
{
    /** @var array<string, string|null>|null The mail sender from .env, captured before any override. */
    private static ?array $configuredSender = null;

    public function __construct(
        private readonly SettingRepositoryInterface $settings,
        private readonly AuditLogService $audit,
        private readonly CacheRepository $cache,
    ) {
        self::$configuredSender ??= [
            SettingKeys::MAIL_FROM_NAME => self::clean(config('mail.from.name')),
            SettingKeys::MAIL_FROM_ADDRESS => self::clean(config('mail.from.address')),
        ];
    }

    /**
     * Every setting, stored values over the defaults.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        $stored = $this->cache->remember(CacheKeys::SETTINGS, CacheKeys::TTL_SECONDS, fn (): array => $this->settings->values());
        $defaults = $this->defaults();
        $values = [];

        foreach (SettingKeys::all() as $key) {
            $values[$key] = self::clean($stored[$key] ?? null) ?? $defaults[$key];
        }

        return $values;
    }

    public function get(string $key): ?string
    {
        $this->guardKey($key);

        return $this->all()[$key];
    }

    /**
     * What applies when nothing has been stored: contact details and the
     * notification address are empty, the mail sender comes from config.
     *
     * @return array<string, string|null>
     */
    public function defaults(): array
    {
        return [
            SettingKeys::CONTACT_EMAIL => null,
            SettingKeys::CONTACT_PHONE => null,
            SettingKeys::CONTACT_ADDRESS => null,
            SettingKeys::NOTIFICATION_EMAIL => null,
            SettingKeys::MAIL_FROM_NAME => self::$configuredSender[SettingKeys::MAIL_FROM_NAME] ?? null,
            SettingKeys::MAIL_FROM_ADDRESS => self::$configuredSender[SettingKeys::MAIL_FROM_ADDRESS] ?? null,
            SettingKeys::CHAT_UPLOADS_ENABLED => '1',
            SettingKeys::CHAT_IMAGE_MAX_MB => (string) ChatAttachments::IMAGE_MAX_MB,
            SettingKeys::CHAT_DOCUMENT_MAX_MB => (string) ChatAttachments::DOCUMENT_MAX_MB,
        ];
    }

    /**
     * Save the given keys. Email addresses are lower-cased; blank values are
     * stored as null (so the default applies). Changes are audited.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string|null>
     */
    public function update(array $values, ?User $actor = null): array
    {
        $before = $this->all();
        $store = [];
        $changes = [];

        foreach ($values as $key => $value) {
            $this->guardKey((string) $key);
            $clean = self::clean($value);

            if ($clean !== null && in_array($key, [SettingKeys::CONTACT_EMAIL, SettingKeys::NOTIFICATION_EMAIL, SettingKeys::MAIL_FROM_ADDRESS], true)) {
                $clean = mb_strtolower($clean);
            }

            $store[$key] = $clean;

            if ($clean !== $before[$key]) {
                $changes[$key] = ['from' => $before[$key], 'to' => $clean];
            }
        }

        $this->settings->putMany($store);
        $this->flushCache();

        if ($changes !== []) {
            $this->audit->record($actor, AuditAction::SETTINGS_UPDATED, null, ['changes' => $changes]);
        }

        $this->applyMailSender();

        return $this->all();
    }

    /**
     * Store values only for keys that have never been saved (seeding).
     *
     * @param  array<string, string|null>  $values
     */
    public function seedDefaults(array $values): void
    {
        $stored = $this->settings->values();
        $missing = [];

        foreach ($values as $key => $value) {
            $this->guardKey((string) $key);

            if (! array_key_exists($key, $stored)) {
                $missing[$key] = self::clean($value);
            }
        }

        if ($missing !== []) {
            $this->settings->putMany($missing);
            $this->flushCache();
        }
    }

    /**
     * Public contact details for the site.
     *
     * @return array{email: string|null, phone: string|null, address: string|null}
     */
    public function contact(): array
    {
        $all = $this->all();

        return [
            'email' => $all[SettingKeys::CONTACT_EMAIL],
            'phone' => $all[SettingKeys::CONTACT_PHONE],
            'address' => $all[SettingKeys::CONTACT_ADDRESS],
        ];
    }

    /**
     * The address that receives tribute and community notifications, or null
     * when none is configured (callers then fall back to the staff accounts).
     */
    public function notificationAddress(): ?string
    {
        return $this->get(SettingKeys::NOTIFICATION_EMAIL);
    }

    /**
     * @return array{name: string|null, address: string|null}
     */
    public function mailSender(): array
    {
        $all = $this->all();

        return ['name' => $all[SettingKeys::MAIL_FROM_NAME], 'address' => $all[SettingKeys::MAIL_FROM_ADDRESS]];
    }

    /**
     * Point mail.from at the stored sender for the rest of this request.
     */
    public function applyMailSender(): void
    {
        $sender = $this->mailSender();

        if ($sender['address'] !== null) {
            config(['mail.from.address' => $sender['address']]);
        }

        if ($sender['name'] !== null) {
            config(['mail.from.name' => $sender['name']]);
        }

        // Mailers already built in this process keep their old "from"; drop them.
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }

    public function flushCache(): void
    {
        $this->cache->forget(CacheKeys::SETTINGS);
    }

    private static function clean(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function guardKey(string $key): void
    {
        if (! SettingKeys::isValid($key)) {
            throw new InvalidArgumentException(sprintf('Unknown setting "%s".', $key));
        }
    }
}
