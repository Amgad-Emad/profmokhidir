@use('App\Constants\ChatCopy')
{{--
    The composer: reply strip, attachment previews (name, size, progress, remove, and an optional
    description for images; left empty, the server describes it as shared by the member), the auto-growing <x-form.textarea> whose text lives only in Alpine
    state (`draft`), the paperclip, "Aa" for the formatting help, and the send button. The
    Enter / Shift+Enter hint retires after three sends; the count appears only near the limit,
    which is enforced on the server (the field has no maxlength, so a paste is never cut short).
--}}
<form class="chat-composer" x-on:submit.prevent="send()" novalidate data-composer>
    <div class="chat-composer__inner">
        <div class="chat-composer__strip" x-show="replyTo" x-cloak data-reply-preview>
            <p class="min-w-0 flex-1 border-l border-accent-line pl-3">
                <span class="t-label text-accent-text">{{ ChatCopy::REPLYING_TO }}</span>
                <span class="smallcaps ml-2 text-ink" x-text="replyTo?.author"></span>
                <span class="chat-reply__excerpt" dir="auto" x-text="replyTo?.excerpt"></span>
            </p>
            <x-button variant="icon" type="button" class="chat-tool" x-on:click="cancelReply()" data-cancel-reply>
                <x-icon name="close" class="size-4" :label="ChatCopy::CANCEL_REPLY" />
            </x-button>
        </div>

        <ul class="chat-tray" x-show="attachments.length > 0" x-cloak aria-label="{{ ChatCopy::ATTACHMENTS_LABEL }}" data-tray>
            <template x-for="a in attachments" :key="a.key">
                <li class="chat-tray__item" :class="{ 'is-failed': a.state === 'failed' }" :data-state="a.state" data-tray-item>
                    <span class="chat-tray__thumb" aria-hidden="true">
                        <img x-show="a.preview" :src="a.preview" alt="">
                        <span class="chat-filemark" x-show="!a.preview" x-text="a.type"></span>
                    </span>
                    <span class="min-w-0">
                        <span class="chat-tray__name" dir="auto" x-text="a.name"></span>
                        <span class="chat-tray__meta"><span x-text="a.type"></span> · <span x-text="a.sizeLabel"></span><span x-show="a.state === 'uploading'"> · {{ ChatCopy::ATTACH_UPLOADING }} <span x-text="a.progress + '%'"></span></span></span>
                        <span class="chat-progress" role="progressbar" x-show="a.state === 'uploading'" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="a.progress" :aria-label="'{{ ChatCopy::ATTACH_UPLOADING }} ' + a.name" data-progress><span :style="`transform: scaleX(${a.progress / 100})`"></span></span>
                        <span class="chat-tray__error" role="alert" x-show="a.error" x-text="a.error"></span>
                        <template x-if="a.kind === 'image'">
                            <span class="block">
                                <label class="sr-only" :for="'chat-alt-' + a.key">{{ ChatCopy::ATTACH_DESCRIPTION_LABEL }}: <span x-text="a.name"></span></label>
                                <input type="text" class="chat-tray__alt" :id="'chat-alt-' + a.key" x-model="a.alt" :maxlength="config.attachments.altMaxLength"
                                       placeholder="{{ ChatCopy::ATTACH_DESCRIPTION_PLACEHOLDER }}"
                                       x-on:keydown.enter.prevent="$refs.draft?.focus()" dir="auto" data-alt>
                            </span>
                        </template>
                    </span>
                    <x-button variant="icon" type="button" class="chat-tool" x-on:click="removeAttachment(a)" x-bind:aria-label="config.copy.attachRemove.replace(':name', a.name)" data-remove-attachment>
                        <x-icon name="close" class="size-4" />
                    </x-button>
                </li>
            </template>
        </ul>

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
                             class="form-style-underline chat-composer__control" control-class="chat-composer__input" :placeholder="ChatCopy::COMPOSER_PLACEHOLDER"
                             :control="[
                                 'x-model' => 'draft',
                                 'x-ref' => 'draft',
                                 'x-on:input' => 'grow()',
                                 'x-on:keydown.enter' => 'onEnter($event)',
                                 'x-on:paste' => 'onPaste($event)',
                                 'x-bind:disabled' => 'expired || accessLost',
                                 'dir' => 'auto',
                                 'aria-describedby' => 'chat-composer-hint',
                             ]" />
            <x-button size="round" type="button" class="chat-clip" x-show="config.attachments.enabled" x-on:click="openPicker()" aria-label="{{ ChatCopy::ATTACH }}" data-tip="{{ ChatCopy::ATTACH }}" aria-describedby="chat-attach-note" data-attach>
                <x-icon name="paperclip" class="size-5" />
            </x-button>
            <input type="file" multiple hidden :accept="config.attachments.accept" x-ref="fileInput" x-on:change="pickFiles($event)" data-file-input>
            <x-button size="round" type="button" class="chat-aa" x-on:click="toggleFormatting()" x-bind:aria-expanded="formattingOpen ? 'true' : 'false'" aria-controls="chat-formatting"
                    aria-label="{{ ChatCopy::FORMATTING_TOGGLE }}" data-tip="{{ ChatCopy::FORMATTING_TOGGLE }}" data-formatting-toggle>
                <span aria-hidden="true">{{ ChatCopy::FORMATTING_BUTTON }}</span>
            </x-button>
            <x-button variant="primary" size="round" type="submit" class="chat-send" x-bind:disabled="!canSend" x-bind:aria-busy="sending ? 'true' : 'false'"
                    aria-label="{{ ChatCopy::SEND }}" data-tip="{{ ChatCopy::SEND }}" data-send>
                <x-icon name="send" class="size-5" />
            </x-button>
        </div>

        <div class="chat-composer__meta">
            <p id="chat-composer-hint" class="chat-hint" x-show="hintShown" data-composer-hint>{{ ChatCopy::COMPOSER_HINT }}</p>
            <p id="chat-attach-note" class="chat-conduct-note" x-show="config.attachments.enabled" data-attach-note>{{ ChatCopy::ATTACH_NOTE }} <span class="sr-only" x-text="config.attachments.types"></span></p>
            <p class="text-sm text-accent-text" role="alert" x-show="sendError" x-text="sendError" x-cloak data-send-error></p>
            <p class="text-sm font-light text-ink-muted" role="status" aria-live="polite" x-show="notice && !sendError" x-text="notice" x-cloak data-notice></p>
            <p class="chat-count" :class="{ 'is-near': remaining <= 0 }" x-show="remaining <= config.countWarningRemaining" x-cloak aria-live="polite" x-text="countText" data-count></p>
        </div>
    </div>
</form>
