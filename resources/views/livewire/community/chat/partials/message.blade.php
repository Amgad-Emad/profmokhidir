@use('App\Constants\ChatCopy')
{{-- One message inside a run. Rendered by Alpine inside x-for="(m, index) in run.messages". --}}
<li class="chat-message"
    :class="{ 'is-sending': m.state === 'sending', 'is-failed': m.state === 'failed', 'is-deleted': m.deleted, 'is-open': openKey === m.key }"
    :id="m.id ? 'message-' + m.id : 'pending-' + m.key"
    :data-message-id="m.id" :data-client-uuid="m.client_uuid"
    tabindex="-1" data-chat-message
    x-on:mouseenter="hoverKey = m.key" x-on:mouseleave="hoverKey = null"
    x-on:focusin="focusKey = m.key" x-on:focusout="if (!$el.contains($event.relatedTarget)) focusKey = null"
    x-on:touchstart.passive="pressStart(m)" x-on:touchend="pressEnd()" x-on:touchmove.passive="pressEnd()" x-on:touchcancel="pressEnd()">

    <time class="sr-only" :datetime="m.created_at" x-text="formatTime(m.created_at)" x-show="index > 0"></time>

    <template x-if="m.reply_to">
        <button type="button" class="chat-reply" x-on:click="jumpTo(m.reply_to.id)" data-reply-quote>
            <span class="t-label text-accent-text mr-2">{{ ChatCopy::REPLYING_TO }}</span>
            <span class="smallcaps chat-reply__author" x-show="!m.reply_to.deleted" x-text="m.reply_to.author"></span>
            <span class="chat-reply__excerpt" :class="{ 'chat-reply__removed': m.reply_to.deleted }" x-text="m.reply_to.excerpt"></span>
        </button>
    </template>

    <template x-if="m.deleted">
        <p class="chat-message__removed" x-text="config.removedPlaceholder"></p>
    </template>

    <template x-if="!m.deleted && !(editing && editing.key === m.key)">
        <div class="chat-message__body" x-html="m.html"></div>
    </template>

    <template x-if="editing && editing.key === m.key">
        <form class="chat-edit" x-on:submit.prevent="saveEdit()">
            <x-form.textarea name="edit" id="chat-edit" :label="ChatCopy::EDIT_LABEL" hide-label :optional="false" :counter="false" rows="3"
                             :control="[
                                 'x-model' => 'editing.body',
                                 ':maxlength' => 'config.bodyMaxLength',
                                 'x-on:keydown.enter.prevent' => 'if (!$event.shiftKey) saveEdit(); else insertNewline($event)',
                                 'x-on:keydown.escape.prevent.stop' => 'cancelEdit()',
                                 'x-init' => '$el.focus()',
                             ]" />
            <p class="form-message form-error" x-show="editing.error" role="alert"><x-icon name="warning" class="mt-1 size-4 shrink-0" /><span x-text="editing.error"></span></p>
            <div class="flex flex-wrap gap-4">
                <button type="submit" class="chat-action" x-bind:disabled="editing.saving">Save</button>
                <button type="button" class="chat-action" x-on:click="cancelEdit()">Cancel</button>
                <button type="button" class="chat-action" x-show="editing.stale" x-on:click="reloadMessage(m)">Reload it</button>
            </div>
        </form>
    </template>

    <p class="chat-message__flags" x-show="(m.edited_at && !m.deleted) || m.pinned || m.state">
        <span class="chat-flag" x-show="m.edited_at && !m.deleted" data-edited>{{ ChatCopy::EDITED }}</span>
        <span class="chat-flag" x-show="m.pinned && !m.deleted" data-pinned>{{ ChatCopy::PINNED }}</span>
        <span class="chat-flag" x-show="m.state === 'sending'" data-sending x-text="config.copy.sending"></span>
        <span class="chat-flag chat-flag--alert" x-show="m.state === 'failed'" role="alert" data-failed>
            <span x-text="config.copy.notSent"></span> · <button type="button" class="chat-action" x-on:click="retry(m)" data-retry x-text="config.copy.retry"></button>
            <span class="normal-case tracking-normal" x-show="m.error" x-text="m.error"></span>
        </span>
    </p>

    {{-- The toolbar is in the document (and the Tab order) only while the message is hovered, focused, or opened with Enter or a long press. --}}
    <div class="chat-message__toolbar" role="toolbar" aria-label="Message actions" x-show="toolbarShown(m)" data-toolbar>
        <button type="button" class="chat-action" x-on:click="startReply(m)" data-reply>{{ ChatCopy::ACTION_REPLY }}</button>
        <button type="button" class="chat-action" x-on:click="copyLink(m)" data-copy-link>{{ ChatCopy::ACTION_COPY_LINK }}</button>
        <button type="button" class="chat-action" x-show="m.can_pin && !m.pinned" x-on:click="pin(m)" data-pin>{{ ChatCopy::ACTION_PIN }}</button>
        <button type="button" class="chat-action" x-show="m.can_pin && m.pinned" x-on:click="unpin(m)" data-unpin>{{ ChatCopy::ACTION_UNPIN }}</button>
        <button type="button" class="chat-action" x-show="m.can_edit" x-on:click="startEdit(m)" data-edit>{{ ChatCopy::ACTION_EDIT }}</button>
        <template x-if="m.can_delete">
            <span>
                <button type="button" class="chat-action" x-show="deleting !== m.key" x-on:click="deleting = m.key" data-delete>{{ ChatCopy::ACTION_DELETE }}</button>
                <span x-show="deleting === m.key" x-cloak class="text-sm font-light text-ink-muted">{{ ChatCopy::DELETE_CONFIRM }} <button type="button" class="chat-action" x-on:click="remove(m)" data-delete-confirm>Yes</button> <button type="button" class="chat-action" x-on:click="deleting = null">No</button></span>
            </span>
        </template>
        <button type="button" class="chat-action" x-show="m.can_report" x-on:click="startReport(m)" data-report>{{ ChatCopy::ACTION_REPORT }}</button>
    </div>
</li>
