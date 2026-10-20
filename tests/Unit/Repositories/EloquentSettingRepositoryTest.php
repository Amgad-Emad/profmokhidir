<?php

namespace Tests\Unit\Repositories;

use App\Constants\SettingKeys;
use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Eloquent\EloquentSettingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentSettingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SettingRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(SettingRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentSettingRepository::class, $this->repository);
    }

    #[Test]
    public function put_creates_then_updates_one_row_per_key(): void
    {
        $this->repository->put(SettingKeys::CONTACT_EMAIL, 'a@example.com');
        $this->repository->put(SettingKeys::CONTACT_EMAIL, 'b@example.com');

        $this->assertSame(1, Setting::query()->count());
        $this->assertSame('b@example.com', $this->repository->find(SettingKeys::CONTACT_EMAIL)?->value);
        $this->assertNull($this->repository->find(SettingKeys::CONTACT_PHONE));
    }

    #[Test]
    public function values_and_all_return_what_is_stored_keyed_by_key(): void
    {
        $this->repository->putMany([SettingKeys::MAIL_FROM_NAME => 'Prof', SettingKeys::MAIL_FROM_ADDRESS => null]);

        $this->assertSame([SettingKeys::MAIL_FROM_ADDRESS => null, SettingKeys::MAIL_FROM_NAME => 'Prof'], $this->repository->values());
        $this->assertSame([SettingKeys::MAIL_FROM_ADDRESS, SettingKeys::MAIL_FROM_NAME], $this->repository->all()->pluck('key')->all());
    }

    #[Test]
    public function forget_removes_a_row_and_reports_whether_there_was_one(): void
    {
        Setting::factory()->contactEmail()->create();

        $this->assertTrue($this->repository->forget(SettingKeys::CONTACT_EMAIL));
        $this->assertFalse($this->repository->forget(SettingKeys::CONTACT_EMAIL));
        $this->assertSame([], $this->repository->values());
    }
}
