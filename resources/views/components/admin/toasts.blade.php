{{--
    The toast stack: the outcome of every save, publish and error, bottom end. One polite live
    region for confirmations and one assertive region for errors; each toast has its icon and text,
    a Close button, and an Undo button after a move to the trash (AdminUx::UNDO_SECONDS). Livewire
    screens dispatch AdminUx::EVENT_TOAST (the NotifiesEditors trait); `initial` is the message
    flashed before a redirect. Alpine component `adminToasts` (resources/js/admin/toasts.js).
--}}
@props(['initial' => null])
@use('App\Constants\AdminUx')
<div class="admin-toasts" x-data="adminToasts({{ \Illuminate\Support\Js::from(['initial' => $initial === null ? null : AdminUx::toast($initial)]) }})"
     x-on:{{ AdminUx::EVENT_TOAST }}.window="push($event.detail)" data-toasts>
    <div role="status" aria-live="polite" class="admin-toasts__region">
        <template x-for="toast in polite" :key="toast.id">
            <div class="admin-toast" x-bind:data-tone="toast.tone" x-on:mouseenter="hold(toast)" x-on:mouseleave="release(toast)" x-on:focusin="hold(toast)" x-on:focusout="release(toast)" data-toast>
                <span class="admin-toast__icon">
                    <x-icon name="check" x-show="toast.tone !== 'info'" />
                    <x-icon name="info" x-show="toast.tone === 'info'" x-cloak />
                </span>
                <p class="admin-toast__message" x-text="toast.message"></p>
                <button type="button" class="admin-toast__undo" x-show="toast.undo" x-on:click="undo(toast)" data-toast-undo>{{ __('Undo') }}</button>
                <button type="button" class="admin-toast__close" x-on:click="dismiss(toast)"><span class="sr-only">{{ __('Dismiss') }}</span><x-icon name="close" class="size-4" /></button>
            </div>
        </template>
    </div>
    <div role="alert" aria-live="assertive" class="admin-toasts__region">
        <template x-for="toast in urgent" :key="toast.id">
            <div class="admin-toast" data-tone="error" data-toast>
                <span class="admin-toast__icon"><x-icon name="warning" /></span>
                <p class="admin-toast__message" x-text="toast.message"></p>
                <button type="button" class="admin-toast__close" x-on:click="dismiss(toast)"><span class="sr-only">{{ __('Dismiss') }}</span><x-icon name="close" class="size-4" /></button>
            </div>
        </template>
    </div>
</div>
