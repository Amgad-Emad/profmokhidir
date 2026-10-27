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
            <span class="t-label text-accent-text me-2">{{ __(ChatCopy::REPLYING_TO) }}</span>
            <span class="smallcaps chat-reply__author" dir="auto" x-show="!m.reply_to.deleted" x-text="m.reply_to.author"></span>
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

    {{-- Attached files: images as a grid of thumbnails that open in the lightbox, documents as cards with Download. --}}
    <template x-if="!m.deleted && m.attachments && m.attachments.length > 0">
        <div class="chat-attachments" data-attachments>
            <ul class="chat-gallery" :class="'is-' + imagesOf(m).length" x-show="imagesOf(m).length > 0" aria-label="{{ __(ChatCopy::ATTACHMENTS_LABEL) }}">
                <template x-for="(a, i) in imagesOf(m)" :key="a.id || a.key">
                    <li>
                        {{-- Until the WebP thumbnail exists, `thumb` is the original (scaled down here); the cell breathes until either has loaded. --}}
                        <button type="button" class="chat-gallery__item" :class="{ 'is-loaded': a.loaded }" x-on:click="openLightbox(m, i, $event.currentTarget)" :aria-label="@js(__(ChatCopy::ATTACH_OPEN_IMAGE).': ') + a.alt"
                                :data-thumb-ready="a.thumb_ready ? 'true' : 'false'" data-attachment-image>
                            <img :src="a.thumb || a.preview" :alt="a.alt" loading="lazy" decoding="async" :width="a.width || null" :height="a.height || null"
                                 x-on:load="a.loaded = true" x-on:error="a.loaded = true">
                        </button>
                    </li>
                </template>
            </ul>
            <ul class="chat-files" :class="'is-' + documentsOf(m).length" x-show="documentsOf(m).length > 0" aria-label="{{ __(ChatCopy::ATTACHMENTS_LABEL) }}">
                <template x-for="a in documentsOf(m)" :key="a.id || a.key">
                    <li class="chat-file" data-attachment-document>
                        <span class="chat-filemark" aria-hidden="true" x-text="a.extension"></span>
                        <span class="min-w-0">
                            <span class="chat-file__name" dir="auto" x-text="a.name"></span>
                            <span class="chat-file__meta"><span x-text="a.type"></span> · <span x-text="a.size_label"></span></span>
                        </span>
                        <a class="chat-file__download" :href="a.url" x-show="a.url" download :aria-label="@js(__(ChatCopy::ATTACH_DOWNLOAD)) + ' ' + a.name" data-download>
                            <x-icon name="download" class="size-4" /><span class="chat-file__download-word" aria-hidden="true">{{ __(ChatCopy::ATTACH_DOWNLOAD) }}</span>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
    </template>

    <template x-if="editing && editing.key === m.key">
        <form novalidate class="chat-edit" x-on:submit.prevent="saveEdit()">
            <x-form.textarea name="edit" id="chat-edit" :label="__(ChatCopy::EDIT_LABEL)" hide-label :optional="false" :counter="false" rows="3"
                             :control="[
                                 'x-model' => 'editing.body',
                                 'x-on:keydown.enter.prevent' => 'if (!$event.shiftKey) saveEdit(); else insertNewline($event)',
                                 'x-on:keydown.escape.prevent.stop' => 'cancelEdit()',
                                 'x-init' => '$el.focus()',
                                 'dir' => 'auto',
                             ]" />
            <p class="form-message form-error" x-show="editing.error" role="alert"><x-icon name="warning" class="mt-1 size-4 shrink-0" /><span x-text="editing.error"></span></p>
            <div class="flex flex-wrap gap-4">
                <x-button variant="quiet" size="sm" type="submit" class="chat-action" x-bind:disabled="editing.saving">{{ __('Save') }}</x-button>
                <x-button variant="quiet" size="sm" type="button" class="chat-action" x-on:click="cancelEdit()">{{ __('Cancel') }}</x-button>
                <x-button variant="quiet" size="sm" type="button" class="chat-action" x-show="editing.stale" x-on:click="reloadMessage(m)">{{ __('Reload it') }}</x-button>
            </div>
        </form>
    </template>

    <p class="chat-message__flags" x-show="(m.edited_at && !m.deleted) || m.pinned || m.state">
        <span class="chat-flag" x-show="m.edited_at && !m.deleted" data-edited>{{ __(ChatCopy::EDITED) }}</span>
        <span class="chat-flag" x-show="m.pinned && !m.deleted" data-pinned>{{ __(ChatCopy::PINNED) }}</span>
        <span class="chat-flag" x-show="m.state === 'sending'" data-sending x-text="config.copy.sending"></span>
        <span class="chat-flag chat-flag--alert" x-show="m.state === 'failed'" role="alert" data-failed>
            <span x-text="config.copy.notSent"></span> · <x-button variant="quiet" size="sm" type="button" class="chat-action" x-on:click="retry(m)" data-retry x-text="config.copy.retry"></x-button>
            <span class="normal-case tracking-normal" x-show="m.error" x-text="m.error"></span>
        </span>
    </p>

    {{--
        A small floating toolbar, shown only while the message is hovered, focused, or opened with
        Enter or a long press (hidden ones are out of the Tab order). Icon buttons carry their name
        as aria-label and as a tooltip (data-tip) on hover and focus.
    --}}
    <div class="chat-toolbar" role="toolbar" aria-label="{{ __('Message actions') }}" x-show="toolbarShown(m)" x-on:keydown.left.prevent.stop="toolbarStep($event, -1)" x-on:keydown.right.prevent.stop="toolbarStep($event, 1)" data-toolbar>
        <span class="contents">
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.id" x-on:click="startReply(m)" aria-label="{{ __(ChatCopy::ACTION_REPLY) }}" data-tip="{{ __(ChatCopy::ACTION_REPLY) }}" data-reply><x-icon name="reply" class="size-4" /></x-button>
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.id" x-on:click="copyLink(m)" aria-label="{{ __(ChatCopy::ACTION_COPY_LINK) }}" data-tip="{{ __(ChatCopy::ACTION_COPY_LINK) }}" data-copy-link><x-icon name="link" class="size-4" /></x-button>
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.can_pin && !m.pinned" x-on:click="pin(m)" aria-label="{{ __(ChatCopy::ACTION_PIN) }}" data-tip="{{ __(ChatCopy::ACTION_PIN) }}" data-pin><x-icon name="pin" class="size-4" /></x-button>
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.can_pin && m.pinned" x-on:click="unpin(m)" aria-label="{{ __(ChatCopy::ACTION_UNPIN) }}" data-tip="{{ __(ChatCopy::ACTION_UNPIN) }}" data-unpin><x-icon name="pin" class="size-4" /></x-button>
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.can_edit" x-on:click="startEdit(m)" aria-label="{{ __(ChatCopy::ACTION_EDIT) }}" data-tip="{{ __(ChatCopy::ACTION_EDIT) }}" data-edit><x-icon name="pencil" class="size-4" /></x-button>
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.can_delete" x-on:click="confirmDelete(m)" aria-label="{{ __(ChatCopy::ACTION_DELETE) }}" data-tip="{{ __(ChatCopy::ACTION_DELETE) }}" data-delete><x-icon name="trash" class="size-4" /></x-button>
            <x-button variant="icon" type="button" class="chat-toolbar__button" x-show="m.can_report" x-on:click="startReport(m)" aria-label="{{ __('chat.action_report') }}" data-tip="{{ __('chat.action_report') }}" data-report><x-icon name="flag" class="size-4" /></x-button>
        </span>
    </div>
</li>
