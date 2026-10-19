<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

/**
 * A one-at-a-time confirmation dialog: the screen remembers which record and
 * which action is awaiting a "yes", the dialog reads them, and performing or
 * cancelling clears them.
 */
trait ConfirmsActions
{
    public ?int $confirmingId = null;

    public ?string $confirmingAction = null;

    public function confirm(string $action, int $id): void
    {
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
    }

    public function cancelConfirmation(): void
    {
        $this->confirmingAction = null;
        $this->confirmingId = null;
    }

    public function isConfirming(string $action): bool
    {
        return $this->confirmingAction === $action && $this->confirmingId !== null;
    }
}
