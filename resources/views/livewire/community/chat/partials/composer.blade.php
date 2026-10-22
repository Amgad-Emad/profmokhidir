@use('App\Constants\ChatCopy')
{{-- The composer: reply strip, auto-growing field, a small evergreen send pill, the hint and the late character count. --}}
<form class="chat-composer" x-on:submit.prevent="send()" data-composer>
    <div class="chat-composer__inner">
        <div class="chat-composer__strip" x-show="replyTo" x-cloak data-reply-preview>
            <p class="min-w-0 flex-1 border-l border-accent-line pl-3">
                <span class="t-label text-accent-text">{{ ChatCopy::REPLYING_TO }}</span>
                <span class="smallcaps ml-2 text-ink" x-text="replyTo?.author"></span>
                <span class="chat-reply__excerpt" x-text="replyTo?.excerpt"></span>
            </p>
            <button type="button" class="chat-tool" x-on:click="cancelReply()" data-cancel-reply>
                <x-icon name="close" class="size-4" :label="ChatCopy::CANCEL_REPLY" />
            </button>
        </div>

        <div class="chat-composer__field">
            <x-form.textarea name="body" id="chat-draft" :label="ChatCopy::COMPOSER_LABEL" hide-label :optional="false" :counter="false" :autogrow="false" rows="1"
                             class="form-style-underline min-w-0 flex-1" control-class="chat-composer__input" :placeholder="ChatCopy::COMPOSER_PLACEHOLDER"
                             :control="[
                                 'x-model' => 'draft',
                                 ':maxlength' => 'config.bodyMaxLength',
                                 'x-ref' => 'draft',
                                 'x-on:input' => 'grow()',
                                 'x-on:keydown.enter' => 'onEnter($event)',
                                 'x-bind:disabled' => 'expired || accessLost',
                                 'aria-describedby' => 'chat-composer-hint',
                             ]" />
            <button type="submit" class="chat-send" x-bind:disabled="sending || expired || accessLost || draft.trim() === ''" data-send>
                <span x-show="!sending">{{ ChatCopy::SEND }}</span>
                <span x-show="sending" x-cloak x-text="config.copy.sending + '…'"></span>
            </button>
        </div>

        <div class="chat-composer__meta">
            <p id="chat-composer-hint" class="chat-hint">{{ ChatCopy::COMPOSER_HINT }}</p>
            <p class="text-sm text-accent-text" role="alert" x-show="sendError" x-text="sendError" x-cloak></p>
            <p class="text-sm font-light text-ink-muted" role="status" aria-live="polite" x-show="notice && !sendError" x-text="notice" x-cloak data-notice></p>
            <p class="chat-count" :class="{ 'is-near': remaining <= 0 }" x-show="remaining <= config.countWarningRemaining" x-cloak aria-live="polite" data-count>
                <span x-text="draft.length"></span> / <span x-text="config.bodyMaxLength"></span>
            </p>
        </div>
    </div>
</form>
