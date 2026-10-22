<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\AdminUx;

/**
 * Toasts for the admin screens. The message goes to the toast stack in the
 * layout (an accessible live region) and stays in this render's session
 * (now, not flashed), so it is never shown a second time on the next page.
 * Redirects still flash 'admin.status'; the layout turns that into a toast.
 */
trait NotifiesEditors
{
    /**
     * Each update starts without the previous action's message, so it is
     * announced once.
     */
    public function hydrateNotifiesEditors(): void
    {
        session()->forget('admin.status');
    }

    /**
     * @param  array{component: string, method: string, args: list<mixed>}|null  $undo
     */
    protected function toast(string $message, string $tone = AdminUx::TOAST_SUCCESS, ?array $undo = null): void
    {
        session()->now('admin.status', $message);
        $this->dispatch(AdminUx::EVENT_TOAST, ...AdminUx::toast($message, $tone, $undo));
    }

    /**
     * An undo button for the toast: calls $method(...$args) on this component,
     * so $args lists the method's parameters (an array parameter is one item).
     *
     * @param  list<mixed>  $args
     * @return array{component: string, method: string, args: list<mixed>}
     */
    protected function undoWith(string $method, array $args): array
    {
        return ['component' => $this->getId(), 'method' => $method, 'args' => $args];
    }
}
