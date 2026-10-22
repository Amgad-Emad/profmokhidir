<?php

namespace Tests\Unit\Constants;

use App\Constants\DashboardGreeting;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardGreetingTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function hours(): array
    {
        return [
            'midnight' => [0, DashboardGreeting::MORNING],
            'late morning' => [11, DashboardGreeting::MORNING],
            'noon' => [12, DashboardGreeting::AFTERNOON],
            'late afternoon' => [17, DashboardGreeting::AFTERNOON],
            'six' => [18, DashboardGreeting::EVENING],
            'last hour' => [23, DashboardGreeting::EVENING],
        ];
    }

    #[Test]
    #[DataProvider('hours')]
    public function the_salutation_follows_the_hour(int $hour, string $expected): void
    {
        $this->assertSame($expected, DashboardGreeting::salutation($hour));
    }

    #[Test]
    public function it_rejects_an_hour_outside_the_day(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DashboardGreeting::salutation(24);
    }

    #[Test]
    public function all_lists_the_three_salutations(): void
    {
        $this->assertSame([DashboardGreeting::MORNING, DashboardGreeting::AFTERNOON, DashboardGreeting::EVENING], DashboardGreeting::all());
    }
}
