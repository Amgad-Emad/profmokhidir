@use('App\Constants\ChatCopy')
{{-- One message inside a run. Rendered by Alpine inside x-for="(m, index) in run.messages". --}}
<li class="chat-message"
    :class="{ 'is-sending': m.state === 'sending', 'is-failed': m.state === 'failed', 'is-deleted': m.deleted, 'is-open': openKey === m.key, 'is-arriving': m.arriving }"
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
            <span class="chat-reply__excerpt" :class="{ 'chat-reply__removed': m.reply_to.deleted }" dir="auto" x-text="m.reply_to.excerpt"></span>
        </button>
    </template>

    <template x-if="m.deleted">
        <p class="chat-message__removed" x-text="config.removedPlaceholder"></p>
    </template>

    <template x-if="!m.deleted && !(editing && editing.key === m.key)">
        {{-- dir="auto" per message, and each line finds its own direction (unicode-bidi: plaintext), so Arabic and English read correctly side by side. --}}
        <div class="chat-message__body" dir="auto" x-html="m.html" data-message-body></div>
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

    {{--
        A small floating toolbar, shown only while the message is hovered, focused, or opened with
        Enter or a long press (hidden ones are out of the Tab order). Icon buttons carry their name
        as aria-label and as a tooltip (data-tip) on hover and focus.
    --}}
    <div class="chat-toolbar" role="toolbar" aria-label="Message actions" x-show="toolbarShown(m)" x-on:keydown.left.prevent.stop="toolbarStep($event, -1)" x-on:keydown.right.prevent.stop="toolbarStep($event, 1)" data-toolbar>
        <template x-if="deleting !== m.key">
            <span class="contents">
                <button type="button" class="chat-toolbar__button" x-show="m.id" x-on:click="startReply(m)" aria-label="{{ ChatCopy::ACTION_REPLY }}" data-tip="{{ ChatCopy::ACTION_REPLY }}" data-reply><x-icon name="reply" class="size-4" /></button>
                <button type="button" class="chat-toolbar__button" x-show="m.id" x-on:click="copyLink(m)" aria-label="{{ ChatCopy::ACTION_COPY_LINK }}" data-tip="{{ ChatCopy::ACTION_COPY_LINK }}" data-copy-link><x-icon name="link" class="size-4" /></button>
                <button type="button" class="chat-toolbar__button" x-show="m.can_pin && !m.pinned" x-on:click="pin(m)" aria-label="{{ ChatCopy::ACTION_PIN }}" data-tip="{{ ChatCopy::ACTION_PIN }}" data-pin><x-icon name="pin" class="size-4" /></button>
                <button type="button" class="chat-toolbar__button" x-show="m.can_pin && m.pinned" x-on:click="unpin(m)" aria-label="{{ ChatCopy::ACTION_UNPIN }}" data-tip="{{ ChatCopy::ACTION_UNPIN }}" data-unpin><x-icon name="pin" class="size-4" /></button>
                <button type="button" class="chat-toolbar__button" x-show="m.can_edit" x-on:click="startEdit(m)" aria-label="{{ ChatCopy::ACTION_EDIT }}" data-tip="{{ ChatCopy::ACTION_EDIT }}" data-edit><x-icon name="pencil" class="size-4" /></button>
                <button type="button" class="chat-toolbar__button" x-show="m.can_delete" x-on:click="confirmDelete(m)" aria-label="{{ ChatCopy::ACTION_DELETE }}" data-tip="{{ ChatCopy::ACTION_DELETE }}" data-delete><x-icon name="trash" class="size-4" /></button>
                <button type="button" class="chat-toolbar__button" x-show="m.can_report" x-on:click="startReport(m)" aria-label="{{ ChatCopy::ACTION_REPORT }}" data-tip="{{ ChatCopy::ACTION_REPORT }}" data-report><x-icon name="flag" class="size-4" /></button>
            </span>
        </template>
        <template x-if="deleting === m.key">
            <span class="chat-toolbar__confirm">
                <span>{{ ChatCopy::DELETE_CONFIRM }}</span>
                <button type="button" class="chat-action" x-on:click="remove(m)" x-init="$nextTick(() => $el.focus())" data-delete-confirm>Yes</button>
                <button type="button" class="chat-action" x-on:click="cancelDelete(m)">No</button>
            </span>
        </template>
    </div>
</li>
