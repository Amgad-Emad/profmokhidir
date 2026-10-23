@use('App\Constants\ChatCopy')
{{--
    The composer: reply strip, the auto-growing <x-form.textarea>, a small "Aa" button for the
    formatting help, and a round send button that stays disabled while the field is empty. The
    Enter / Shift+Enter hint sits under the field until this browser has sent three messages; the
    count appears only from 1,800 characters.
--}}
<form class="chat-composer" x-on:submit.prevent="send()" data-composer>
    <div class="chat-composer__inner">
        <div class="chat-composer__strip" x-show="replyTo" x-cloak data-reply-preview>
            <p class="min-w-0 flex-1 border-l border-accent-line pl-3">
                <span class="t-label text-accent-text">{{ ChatCopy::REPLYING_TO }}</span>
                <span class="smallcaps ml-2 text-ink" x-text="replyTo?.author"></span>
                <span class="chat-reply__excerpt" dir="auto" x-text="replyTo?.excerpt"></span>
            </p>
            <button type="button" class="chat-tool" x-on:click="cancelReply()" data-cancel-reply>
                <x-icon name="close" class="size-4" :label="ChatCopy::CANCEL_REPLY" />
            </button>
        </div>

        <div id="chat-formatting" class="chat-formatting" x-show="formattingOpen" x-cloak data-formatting>
            <p class="t-label text-accent-text">{{ ChatCopy::FORMATTING_HEADING }}</p>
            <dl class="chat-formatting__list">
                @foreach ($hints as $hint)
                    <div>
                        <dt><code class="chat-code">{{ $hint['markup'] }}</code></dt>
                        <dd>{{ $hint['means'] }}</dd>
                    </div>
                @endforeach
            </dl>
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
                                 'dir' => 'auto',
                                 'aria-describedby' => 'chat-composer-hint',
                             ]" />
            <button type="button" class="chat-aa" x-on:click="toggleFormatting()" :aria-expanded="formattingOpen ? 'true' : 'false'" aria-controls="chat-formatting"
                    aria-label="{{ ChatCopy::FORMATTING_TOGGLE }}" data-tip="{{ ChatCopy::FORMATTING_TOGGLE }}" data-formatting-toggle>
                <span aria-hidden="true">{{ ChatCopy::FORMATTING_BUTTON }}</span>
            </button>
            <button type="submit" class="chat-send" x-bind:disabled="sending || expired || accessLost || draft.trim() === ''" :aria-busy="sending ? 'true' : 'false'"
                    aria-label="{{ ChatCopy::SEND }}" data-tip="{{ ChatCopy::SEND }}" data-send>
                <x-icon name="send" class="size-5" />
            </button>
        </div>

        <div class="chat-composer__meta">
            <p id="chat-composer-hint" class="chat-hint" x-show="hintShown" data-composer-hint>{{ ChatCopy::COMPOSER_HINT }}</p>
            <p class="text-sm text-accent-text" role="alert" x-show="sendError" x-text="sendError" x-cloak></p>
            <p class="text-sm font-light text-ink-muted" role="status" aria-live="polite" x-show="notice && !sendError" x-text="notice" x-cloak data-notice></p>
            <p class="chat-count" :class="{ 'is-near': remaining <= 0 }" x-show="remaining <= config.countWarningRemaining" x-cloak aria-live="polite" x-text="countText" data-count></p>
        </div>
    </div>
</form>
