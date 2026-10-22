<?php

namespace Tests\Unit\Constants;

use App\Constants\AuthScreens;
use App\Constants\Taglines;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthScreensTest extends TestCase
{
    #[Test]
    public function every_screen_has_a_title_an_intro_an_action_and_a_busy_label(): void
    {
        $this->assertSame(
            [AuthScreens::LOGIN, AuthScreens::FORGOT_PASSWORD, AuthScreens::RESET_PASSWORD, AuthScreens::VERIFY_EMAIL, AuthScreens::CONFIRM_PASSWORD, AuthScreens::TWO_FACTOR, AuthScreens::COMMUNITY_SIGNUP],
            AuthScreens::keys(),
        );

        foreach (AuthScreens::screens() as $key => $screen) {
            $this->assertSame(['title', 'intro', 'action', 'busy'], array_keys($screen), $key);

            foreach ($screen as $field => $value) {
                $this->assertNotSame('', trim($value), "{$key}.{$field}");
            }

            $this->assertStringEndsWith('…', $screen['busy'], "{$key} busy label trails off quietly");
            $this->assertSame($screen, AuthScreens::screen($key));
        }
    }

    #[Test]
    public function an_unknown_screen_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AuthScreens::screen('register');
    }

    #[Test]
    public function the_epigraph_is_the_about_epigraph_and_the_code_has_six_digits(): void
    {
        $this->assertSame(Taglines::ABOUT_EPIGRAPH, AuthScreens::EPIGRAPH);
        $this->assertSame('Knowledge is the light we pass on.', AuthScreens::EPIGRAPH);
        $this->assertSame(6, AuthScreens::CODE_LENGTH);
        $this->assertSame(400, AuthScreens::MEASURE_PX);
        $this->assertSame('These credentials do not match our records.', AuthScreens::FAILED_CREDENTIALS);
    }

    #[Test]
    public function password_strength_is_judged_by_length_alone(): void
    {
        $this->assertSame('short', AuthScreens::passwordStrength(0)['key']);
        $this->assertSame('short', AuthScreens::passwordStrength(AuthScreens::PASSWORD_MIN_LENGTH - 1)['key']);
        $this->assertSame('fair', AuthScreens::passwordStrength(AuthScreens::PASSWORD_MIN_LENGTH)['key']);
        $this->assertSame('good', AuthScreens::passwordStrength(AuthScreens::PASSWORD_GOOD_LENGTH)['key']);
        $this->assertSame('strong', AuthScreens::passwordStrength(AuthScreens::PASSWORD_STRONG_LENGTH)['key']);
        $this->assertSame('Strong', AuthScreens::passwordStrength(64)['label']);

        $levels = AuthScreens::passwordStrengthLevels();
        $this->assertSame(['short', 'fair', 'good', 'strong'], array_column($levels, 'key'));
        $this->assertSame(array_column($levels, 'from'), array_values(array_unique(array_column($levels, 'from'))));
        $sorted = array_column($levels, 'from');
        sort($sorted);
        $this->assertSame($sorted, array_column($levels, 'from'), 'Levels rise with length');
        $this->assertStringContainsString('Length', AuthScreens::PASSWORD_HINT, 'The hint asks for length, not character rules');
        $this->assertStringContainsString('twelve', AuthScreens::PASSWORD_HINT);
    }
}
