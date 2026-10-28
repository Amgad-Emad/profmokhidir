<?php

namespace Database\Factories;

use App\Constants\AdminNavigation;
use App\Constants\ListDensity;
use App\Constants\ListToolbar;
use App\Models\ListPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListPreference>
 */
class ListPreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'screen' => AdminNavigation::PUBLICATIONS,
            'state' => [],
            'density' => null,
        ];
    }

    public function forScreen(string $screen): static
    {
        return $this->state(['screen' => $screen]);
    }

    /**
     * A remembered view: drafts of the screen, searched for a word.
     */
    public function drafts(): static
    {
        return $this->state(['state' => [
            ListToolbar::PARAM_SEARCH => '',
            ListToolbar::PARAM_FILTERS => ['status' => 'draft'],
            ListToolbar::PARAM_TAB => 'drafts',
        ]]);
    }

    public function compact(): static
    {
        return $this->state(['density' => ListDensity::Compact]);
    }
}
