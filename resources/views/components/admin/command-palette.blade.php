{{--
    The command palette (Ctrl/Cmd+K, or the search button in the top bar): one field that jumps to
    any screen, starts something new ("New publication") or finds content by title. Screens and
    actions are filtered in the browser; content comes from the admin.search JSON route. A modal
    dialog with an ARIA combobox and grouped listbox: arrows move, Enter opens, Escape closes.
    Alpine component `commandPalette` (resources/js/admin/palette.js).
--}}
@props(['entries'])
@use('App\Constants\AdminUx')
<div class="palette-root" x-data="commandPalette({{ \Illuminate\Support\Js::from(['entries' => $entries, 'endpoint' => route(AdminUx::SEARCH_ROUTE), 'min' => AdminUx::SEARCH_MIN_LENGTH]) }})"
     x-on:admin-palette-open.window="show()" data-command-palette>
    <div class="palette-overlay" x-show="open" x-cloak x-on:click.self="close()" x-on:keydown.escape.prevent.stop="close()">
        <div class="palette" role="dialog" aria-modal="true" aria-labelledby="palette-title" x-trap.noscroll="open">
            <h2 id="palette-title" class="sr-only">{{ __('Search or jump to') }}</h2>
            <div class="palette__field">
                <x-icon name="search" class="size-5 text-ink-muted" />
                <input type="text" class="palette__input" x-ref="input" x-model="query" x-on:input="search()"
                       role="combobox" aria-expanded="true" aria-controls="palette-results" aria-autocomplete="list"
                       x-bind:aria-activedescendant="activeId" aria-label="{{ __('Screen, action or title') }}"
                       placeholder="{{ __('Type a screen, an action or a title') }}" autocomplete="off" spellcheck="false"
                       x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="choose()">
                <kbd class="admin-kbd">Esc</kbd>
            </div>
            <div id="palette-results" class="palette__results" role="listbox" aria-label="{{ __('Results') }}">
                <template x-for="group in groups" :key="group.name">
                    <div role="group" x-bind:aria-labelledby="group.id">
                        <p class="palette__group t-label" x-bind:id="group.id" role="presentation" x-text="group.name"></p>
                        <template x-for="item in group.items" :key="item.id">
                            <div class="palette__option" role="option" x-bind:id="item.id" x-bind:aria-selected="(item.id === activeId).toString()"
                                 x-on:click="go(item)" x-on:mousemove="activeId = item.id">
                                <span class="palette__label" x-text="item.label"></span>
                                <span class="palette__hint" x-text="item.status ? item.hint + ' · ' + item.status : item.hint"></span>
                            </div>
                        </template>
                    </div>
                </template>
                <p class="palette__empty" x-show="groups.length === 0 && !loading" x-text="query.trim() === '' ? '' : {{ \Illuminate\Support\Js::from(__('Nothing matches “:term”. Try another word.')) }}.replace(':term', query.trim())"></p>
            </div>
            <p class="palette__foot">
                <span><kbd class="admin-kbd">↑</kbd><kbd class="admin-kbd">↓</kbd> {{ __('move') }}</span>
                <span><kbd class="admin-kbd">Enter</kbd> {{ __('open') }}</span>
                <span x-show="loading" x-cloak>{{ __('Searching…') }}</span>
            </p>
            <p class="sr-only" role="status" aria-live="polite" x-text="announcement"></p>
        </div>
    </div>
</div>
