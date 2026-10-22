{{--
    "Leave without saving?": shown when a link is followed while an edit screen has unsaved
    changes (the browser's own prompt covers closing the tab or reloading). Stay keeps the
    changes on screen; Leave follows the link. Alpine component `leaveGuard`
    (resources/js/admin/editing.js); edit screens report their state with the
    `admin-dirty` window event.
--}}
<div x-data="leaveGuard" data-leave-guard>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-show="asking" x-cloak x-on:keydown.escape.prevent.stop="stay()">
        <div role="alertdialog" aria-modal="true" aria-labelledby="leave-title" aria-describedby="leave-description" class="admin-dialog" x-trap.noscroll="asking">
            <h2 id="leave-title" class="font-display text-3xl font-light leading-tight">Leave without saving?</h2>
            <p id="leave-description" class="mt-3 text-base font-light text-ink">Your latest changes on this page have not been saved yet. If you leave now they will be lost.</p>
            <div class="mt-8 flex flex-wrap justify-end gap-3">
                <x-button type="button" variant="outline" x-on:click="leave()" data-leave-confirm>Leave without saving</x-button>
                <x-button type="button" variant="primary" x-on:click="stay()" data-leave-stay>Stay and keep editing</x-button>
            </div>
        </div>
    </div>
</div>
