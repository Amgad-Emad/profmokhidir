@use('App\Constants\ChatCopy')
@use('App\Constants\ChatSettings')
{{--
    "About this room": three tabs. About (the room, its conduct), Pinned, and Members (everyone
    approved, with initials, affiliation, who has been here in the last five minutes and the
    member's own entry; a search from 21 members; for administrators a small menu per member).
    A column on the right from 1024px (collapsible), a drawer below. Only the body scrolls.
--}}
<div class="chat-panel__backdrop" x-show="panelOpen && !isWide" x-cloak x-on:click="closePanel()" aria-hidden="true"></div>
<aside id="chat-panel" class="chat-panel" aria-labelledby="chat-panel-title" x-show="panelOpen" x-cloak x-trap.inert.noscroll="panelOpen && !isWide" data-chat-panel>
    <div class="chat-panel__head">
        <h2 id="chat-panel-title" class="chat-panel__title">{{ ChatCopy::PANEL_TITLE }}</h2>
        <x-button variant="icon" type="button" class="chat-tool" x-on:click="closePanel()" data-panel-close>
            <x-icon name="close" :label="ChatCopy::PANEL_CLOSE" />
        </x-button>
    </div>

    @php($tabs = [ChatSettings::TAB_ABOUT => ChatCopy::TAB_ABOUT, ChatSettings::TAB_PINNED => ChatCopy::TAB_PINNED, ChatSettings::TAB_MEMBERS => ChatCopy::TAB_MEMBERS])
    <div class="chat-tabs" role="tablist" aria-label="{{ ChatCopy::PANEL_TABS_LABEL }}" x-on:keydown="onTabKeydown($event)" data-tabs>
        @foreach ($tabs as $tab => $label)
            <button type="button" role="tab" class="chat-tab" id="chat-tab-{{ $tab }}" aria-controls="chat-tabpanel-{{ $tab }}"
                    :aria-selected="panelTab === @js($tab) ? 'true' : 'false'" :tabindex="panelTab === @js($tab) ? 0 : -1"
                    x-on:click="selectTab(@js($tab))" data-tab="{{ $tab }}">
                {{ $label }}
                @if ($tab === ChatSettings::TAB_PINNED)<span class="chat-tab__count" x-show="pins.length > 0" x-text="pins.length"></span>@endif
                @if ($tab === ChatSettings::TAB_MEMBERS)<span class="chat-tab__count" x-text="members.length"></span>@endif
            </button>
        @endforeach
    </div>

    <div class="chat-panel__body">
        <section role="tabpanel" id="chat-tabpanel-{{ ChatSettings::TAB_ABOUT }}" aria-labelledby="chat-tab-{{ ChatSettings::TAB_ABOUT }}" x-show="panelTab === 'about'" data-tabpanel="about">
            <div class="chat-panel__section">
                <h3 class="t-label text-accent-text">{{ ChatSettings::ROOM_NAME }}</h3>
                <p class="chat-panel__text mt-2" x-text="currentChannel?.description || @js($chatIntro)"></p>
                <p class="mt-2 text-sm font-light text-ink-muted">{{ $chatIntro }}</p>
            </div>

            <div class="chat-panel__section">
                <h3 class="t-label text-accent-text">{{ ChatCopy::PANEL_CONDUCT }}</h3>
                <ol class="chat-panel__list">
                    @foreach ($conduct as $index => $rule)
                        <li class="chat-panel__rule"><span class="smallcaps text-accent-text" aria-hidden="true">{{ app(\App\Services\TypesetterService::class)->roman($index + 1) }}</span><span>{{ $rule }}</span></li>
                    @endforeach
                </ol>
                <p class="chat-conduct-note mt-3" x-show="config.attachments.enabled">{{ ChatCopy::ATTACH_NOTE }}</p>
            </div>
        </section>

        <section role="tabpanel" id="chat-tabpanel-{{ ChatSettings::TAB_PINNED }}" aria-labelledby="chat-tab-{{ ChatSettings::TAB_PINNED }}" class="chat-panel__section" x-show="panelTab === 'pinned'" x-cloak data-pins>
            <p class="text-sm font-light text-ink-muted" x-show="pins.length === 0">{{ ChatCopy::NO_PINS }}</p>
            <ol class="chat-panel__list" x-show="pins.length > 0">
                <template x-for="p in pins" :key="p.id">
                    <li>
                        <button type="button" class="chat-pin" x-on:click="jumpTo(p.id)" data-pin-link>
                            <span class="smallcaps text-ink" x-text="p.author"></span>
                            <time class="chat-time ml-2" :datetime="p.created_at" x-text="formatDateTime(p.created_at)"></time>
                            <span class="chat-pin__excerpt" dir="auto" x-text="p.excerpt"></span>
                        </button>
                        <x-button variant="quiet" size="sm" type="button" class="chat-action ml-3" x-show="canModerate" x-on:click="unpin({ id: p.id })" data-unpin-panel>{{ ChatCopy::ACTION_UNPIN }}</x-button>
                    </li>
                </template>
            </ol>
        </section>

        {{-- Everyone approved. "Online" means the chat was open in the last five minutes (the read cursors the poll already refreshes). --}}
        <section role="tabpanel" id="chat-tabpanel-{{ ChatSettings::TAB_MEMBERS }}" aria-labelledby="chat-tab-{{ ChatSettings::TAB_MEMBERS }}" class="chat-panel__section" x-show="panelTab === 'members'" x-cloak data-members>
            <p class="chat-members__summary" data-members-summary>
                <span x-text="members.length"></span> <span x-text="members.length === 1 ? 'member' : 'members'"></span> ·
                <span x-text="onlineMembers.length" data-online-count></span> {{ ChatCopy::MEMBER_ONLINE }}
            </p>

            <div class="mt-3" x-show="members.length > config.memberSearchThreshold" data-member-search>
                <x-form.input type="search" name="member_search" id="chat-member-search" :label="ChatCopy::MEMBER_SEARCH_LABEL" :optional="false" size="compact" class="form-style-underline"
                              :placeholder="ChatCopy::MEMBER_SEARCH_PLACEHOLDER" autocomplete="off" maxlength="100"
                              :control="['x-model.debounce.150ms' => 'memberSearch']" />
            </div>
            <p class="mt-3 text-sm font-light text-ink-muted" role="status" x-show="memberSearch.trim() !== '' && filteredMembers.length === 0" x-text="config.copy.memberSearchNone"></p>

            <ul class="chat-members" data-members-list>
                <template x-for="member in filteredMembers" :key="member.id">
                    <li data-member :data-member-id="member.id">
                        <div class="chat-member">
                            <span class="chat-avatar chat-member__avatar" :class="{ 'is-online': isOnline(member) }" aria-hidden="true" x-text="member.initials"></span>
                            <span class="chat-member__text">
                                <span class="smallcaps chat-member__name text-ink" x-text="member.name"></span>
                                <span class="t-label text-accent-text ml-1" x-show="member.id === viewer.id" data-you>{{ ChatCopy::STATUS_YOU }}</span>
                                <span class="chat-member__state">
                                    <span x-show="member.affiliation" x-text="member.affiliation"></span><span x-show="member.affiliation && isOnline(member)"> · </span><span x-show="isOnline(member)" data-online>{{ ChatCopy::MEMBER_ONLINE }}</span>
                                </span>
                            </span>
                            <template x-if="member.profile_url || member.can_suspend">
                                <span class="chat-member__actions" x-on:keydown.escape.stop="closeMemberMenu(member)">
                                    <x-button variant="icon" type="button" class="chat-tool" aria-haspopup="true" x-bind:aria-expanded="memberMenu === member.id ? 'true' : 'false'"
                                            x-bind:aria-controls="'chat-member-menu-' + member.id" x-bind:aria-label="config.copy.memberMenu.replace(':name', member.name)"
                                            x-on:click="toggleMemberMenu(member)" data-member-menu-button>
                                        <x-icon name="more" class="size-4" />
                                    </x-button>
                                    <span class="chat-member__menu" :id="'chat-member-menu-' + member.id" x-show="memberMenu === member.id" x-on:click.outside="memberMenu = null" data-member-menu>
                                        <a class="chat-member-menu__item" :href="member.profile_url" x-show="member.profile_url" data-view-profile>{{ ChatCopy::MEMBER_VIEW_PROFILE }}</a>
                                        <button type="button" class="chat-member-menu__item" x-show="member.can_suspend" x-on:click="askSuspend(member)" data-suspend>{{ ChatCopy::MEMBER_SUSPEND }}</button>
                                    </span>
                                </span>
                            </template>
                        </div>
                        <div class="chat-member__confirm" x-show="suspending === member.id" data-suspend-confirm>
                            <span x-text="config.copy.suspendConfirm.replace(':name', member.name)"></span>
                            <span class="flex gap-4">
                                <x-button variant="danger" size="sm" type="button" x-on:click="suspend(member)" data-suspend-yes>Yes, suspend</x-button>
                                <x-button variant="quiet" size="sm" type="button" class="chat-action" x-on:click="cancelSuspend(member)">Cancel</x-button>
                            </span>
                        </div>
                    </li>
                </template>
            </ul>
        </section>
    </div>
</aside>
