@php
    use App\Constants\Brand;
    use App\Constants\Motion;
    use App\Constants\Typography;

    // Colourful sample scene so the photo treatments visibly change it (no external image needed).
    $sample = base64_encode(<<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"><defs><linearGradient id="s" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5aa0e6"/><stop offset="1" stop-color="#d9ecff"/></linearGradient></defs><rect width="400" height="300" fill="url(#s)"/><circle cx="320" cy="70" r="36" fill="#f4b942"/><path d="M0 210 Q100 150 200 200 T400 190 V300 H0Z" fill="#4e8c3a"/><path d="M0 250 Q140 200 400 240 V300 H0Z" fill="#2f6b2a"/><rect x="70" y="170" width="90" height="70" fill="#e8dcc0"/><path d="M60 172 L115 125 L170 172Z" fill="#b5443a"/><rect x="100" y="200" width="22" height="40" fill="#6a4a2e"/><rect x="128" y="192" width="18" height="18" fill="#9ad0f0"/></svg>
SVG);
    $sampleSrc = 'data:image/svg+xml;base64,'.$sample;
    $ratio = fn (array $c): string => number_format($c['ratio'], 2).':1 '.($c['normal'] ? 'AA' : ($c['large'] ? 'AA large' : 'fail'));
@endphp
<x-layouts.public title="Style guide">
    {{-- Hero: scroll-scale + staggered rise + glow --}}
    <section class="glow-brass on-dark bg-evergreen text-champagne">
        <div class="mx-auto flex max-w-7xl flex-col items-start gap-8 px-4 py-28 sm:px-6 lg:py-36" data-rise-group data-scroll-scale>
            <p class="t-label text-brass">Design system · local only</p>
            <h1 class="t-display">Style guide</h1>
            <p class="t-quote max-w-2xl">&ldquo;Knowledge is the light we pass on.&rdquo;</p>
            <p class="t-body max-w-2xl">Every colour, type style, button, card, rule, image treatment and motion pattern in one place. This page is served only in the local environment.</p>
            <nav aria-label="Sections" class="flex flex-wrap gap-3">
                @foreach (['colours' => 'Colours', 'type' => 'Type', 'buttons' => 'Buttons', 'forms' => 'Forms', 'cards' => 'Cards', 'rules' => 'Rules', 'mark' => 'Mark', 'photos' => 'Photos', 'motion' => 'Motion', 'press' => 'Press', 'components' => 'Components'] as $id => $label)
                    <x-button href="#{{ $id }}">{{ $label }}</x-button>
                @endforeach
            </nav>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 sm:px-6">

        {{-- Colours --}}
        <section id="colours" class="py-20" aria-labelledby="colours-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>01 · Colours</p>
            <h2 id="colours-title" class="t-heading mt-3" data-rise>Nine colours, nothing else</h2>
            <p class="t-body mt-4 max-w-2xl text-ash" data-rise>Tailwind's palette is removed; only these tokens compile. Contrast is WCAG 2.2 (AA needs 4.5:1 for text, 3:1 for large text and UI).</p>
            <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-rise-group>
                @foreach ($colours as $colour)
                    <li class="card card-ivory flex flex-col gap-4 !p-0 overflow-hidden">
                        <div class="h-28 w-full" style="background-color: var(--color-{{ $colour['name'] }})"></div>
                        <div class="px-6 pb-6">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-display text-2xl capitalize">{{ $colour['name'] }}</h3>
                                <code class="font-mono text-sm text-ash">{{ $colour['hex'] }}</code>
                            </div>
                            <p class="t-label mt-2 text-bronze">{{ $colour['role'] }}</p>
                            <p class="mt-3 text-sm leading-relaxed text-ash">{{ $colour['usage'] }}</p>
                            <dl class="mt-4 grid grid-cols-2 gap-2 font-mono text-xs text-ash">
                                <div><dt class="t-label !tracking-[0.2em]">On ivory</dt><dd class="mt-1">{{ $ratio($colour['onIvory']) }}</dd></div>
                                <div><dt class="t-label !tracking-[0.2em]">On evergreen</dt><dd class="mt-1">{{ $ratio($colour['onEvergreen']) }}</dd></div>
                            </dl>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8 grid gap-5 sm:grid-cols-4" data-rise-group>
                <div class="card card-ivory"><p class="t-label text-bronze">Evergreen</p><p class="font-display text-4xl">{{ Brand::EVERGREEN_SHARE }}%</p></div>
                <div class="card card-ivory"><p class="t-label text-bronze">Ivory</p><p class="font-display text-4xl">{{ Brand::IVORY_SHARE }}%</p></div>
                <div class="card card-ivory"><p class="t-label text-bronze">Cypress</p><p class="font-display text-4xl">{{ Brand::CYPRESS_SHARE }}%</p></div>
                <div class="card card-ivory"><p class="t-label text-bronze">Brass</p><p class="font-display text-4xl">{{ Brand::BRASS_SHARE }}%</p></div>
            </div>
            <h3 class="t-label mt-14 text-bronze" data-rise>Functional exception: danger red, only for destructive actions and errors</h3>
            <p class="t-body mt-3 max-w-2xl text-ash" data-rise>Never decoration. Used only through the <code class="font-mono text-sm">--danger</code>, <code class="font-mono text-sm">--danger-surface</code> and <code class="font-mono text-sm">--danger-ink</code> tokens: the outline danger button, the filled danger button inside a confirmation dialog, error text, error borders and error summaries.</p>
            <ul class="mt-6 grid gap-5 sm:grid-cols-2" data-rise-group data-functional-colours>
                @foreach ($functionalColours as $colour)
                    <li class="card card-ivory flex flex-col gap-4 !p-0 overflow-hidden" data-functional-colour="{{ $colour['name'] }}">
                        <div class="h-20 w-full" style="background-color: var(--functional-{{ $colour['name'] }})"></div>
                        <div class="px-6 pb-6">
                            <div class="flex items-baseline justify-between gap-3">
                                <h4 class="font-display text-2xl">{{ $colour['name'] }}</h4>
                                <code class="font-mono text-sm text-ash">{{ $colour['hex'] }}</code>
                            </div>
                            <p class="t-label mt-2 text-bronze">{{ $colour['role'] }}</p>
                            <p class="mt-3 text-sm leading-relaxed text-ash">{{ $colour['usage'] }}</p>
                            <dl class="mt-4 grid grid-cols-2 gap-2 font-mono text-xs text-ash">
                                @foreach ($colour['contrast'] as $surface => $contrast)
                                    <div><dt class="t-label !tracking-[0.2em]">On {{ $surface }}</dt><dd class="mt-1">{{ $ratio($contrast) }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Type --}}
        <section id="type" class="py-20" aria-labelledby="type-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>02 · Type</p>
            <h2 id="type-title" class="t-heading mt-3" data-rise>Type scale</h2>
            <dl class="mt-8 grid gap-4 sm:grid-cols-3" data-rise-group>
                @foreach ($fonts as $token => $font)
                    <div class="card">
                        <dt class="t-label text-bronze">font-{{ $token }}</dt>
                        <dd class="mt-2 text-2xl {{ ['display' => 'font-display', 'sans' => 'font-sans', 'mono' => 'font-mono'][$token] }}">{{ $font['family'] }}</dd>
                        <dd class="mt-1 font-mono text-xs text-ash">{{ implode(' · ', $font['weights']) }}{{ $font['italic'] ? ' · italic' : '' }}</dd>
                        <dd class="mt-3 text-sm text-ash">{{ $font['use'] }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-12 space-y-12">
                @foreach ($typeScale as $token => $style)
                    <div class="grid gap-4 border-b border-stone pb-10 md:grid-cols-[12rem_1fr]" data-rise>
                        <div>
                            <p class="t-label text-bronze">.{{ $style['class'] }}</p>
                            <p class="mt-2 font-mono text-xs leading-relaxed text-ash">
                                {{ $style['size'] }}px{{ $style['mobile'] !== $style['size'] ? ' → '.$style['mobile'].'px mobile' : '' }}<br>
                                {{ $style['font'] }} {{ $style['weight'] }}{{ $style['italic'] ? ' italic' : '' }}<br>
                                leading {{ $style['lineHeight'] }}{{ $style['tracking'] ? ' · tracking '.$style['tracking'] : '' }}{{ $style['uppercase'] ? ' · uppercase' : '' }}
                            </p>
                        </div>
                        <p class="{{ $style['class'] }} {{ $token === 'label' ? 'text-bronze' : '' }}">{{ $style['sample'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card">
                    <p class="t-label text-bronze">Links, focus and selection on light</p>
                    <p class="t-body mt-3">Links <a href="#type">underline on hover</a>. Tab to a <x-button type="button" class="ml-1">control</x-button> to see the bronze focus ring. Select this text to see the linen highlight.</p>
                </div>
                <div class="card on-dark border-cypress bg-evergreen text-champagne">
                    <p class="t-label text-brass">Links, focus and selection on dark</p>
                    <p class="t-body mt-3">Links <a href="#type">underline on hover</a>. Tab to a <x-button type="button" class="ml-1">control</x-button> to see the brass focus ring. Monospace is for <code class="font-mono text-sm">technical specs</code> only.</p>
                </div>
            </div>
        </section>

        {{-- Buttons --}}
        <section id="buttons" class="py-20" aria-labelledby="buttons-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>03 · Buttons</p>
            <h2 id="buttons-title" class="t-heading mt-3" data-rise>Buttons</h2>
            <p class="t-body mt-4 max-w-measure text-ink-muted" data-rise>One component, <code class="font-mono text-sm">&lt;x-button variant="…"&gt;</code>, six variants. Destructive actions are always danger: the outline (Move to trash, Remove, Reject, Suspend, Delete message, with the trash icon for deletes) and, only as the final confirm inside a confirmation dialog, the filled danger pill. Each variant takes its background, text, border and icon for every state (default, hover, active, focus, disabled, loading) from the <code class="font-mono text-sm">.btn</code> token table in tokens.css, in both themes. Hover is subtle and never changes the ink of a solid pill: evergreen deepens, ivory takes a linen tint, outlines gain a faint tint. Never add colour utilities to a button.</p>
            @php
                $buttonSurfaces = [
                    'page' => ['label' => 'On the page', 'class' => 'bg-surface'],
                    'raised' => ['label' => 'On a card', 'class' => 'card'],
                    'band' => ['label' => 'On evergreen', 'class' => 'on-dark bg-surface text-ink'],
                ];
            @endphp
            <div class="mt-8 grid gap-5 lg:grid-cols-3" data-rise-group>
                @foreach ($buttonSurfaces as $surfaceKey => $surface)
                    <div class="{{ $surface['class'] }} rounded-card border border-rule p-6" data-button-surface="{{ $surfaceKey }}">
                        <p class="t-label text-accent-text">{{ $surface['label'] }}</p>
                        <ul class="mt-5 space-y-5">
                            @foreach (\App\Constants\ButtonVariant::cases() as $buttonVariant)
                                <li class="flex flex-wrap items-center gap-3" data-button-row="{{ $buttonVariant->value }}">
                                    @if ($buttonVariant === \App\Constants\ButtonVariant::Icon)
                                        <x-button type="button" :variant="$buttonVariant->value" data-button-sample="{{ $buttonVariant->value }}"><x-icon name="search" label="Search" /></x-button>
                                        <x-button type="button" :variant="$buttonVariant->value" disabled><x-icon name="search" label="Search (disabled)" /></x-button>
                                    @else
                                        <x-button type="button" :variant="$buttonVariant->value" data-button-sample="{{ $buttonVariant->value }}">{{ ucfirst($buttonVariant->value) }}</x-button>
                                        <x-button type="button" :variant="$buttonVariant->value" disabled>Disabled</x-button>
                                        <x-button type="button" :variant="$buttonVariant->value" aria-busy="true">Saving…</x-button>
                                    @endif
                                </li>
                            @endforeach
                            <li class="flex flex-wrap items-center gap-3" data-button-row="danger-use">
                                <x-button type="button" variant="danger" size="sm" data-button-sample="danger-trash"><x-icon name="trash" class="size-4" />Move to trash</x-button>
                                <x-button type="button" variant="primary" size="sm">Small</x-button>
                                <x-button type="button" variant="primary" size="round"><x-icon name="send" label="Send" /></x-button>
                                <x-button href="#buttons">As a link</x-button>
                            </li>
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        @include('styleguide.partials.forms')

        {{-- Cards --}}
        <section id="cards" class="py-20" aria-labelledby="cards-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>04 · Cards</p>
            <h2 id="cards-title" class="t-heading mt-3" data-rise>Cards</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-3" data-rise-group>
                <article class="card">
                    <p class="t-label text-bronze">.card · linen</p>
                    <h3 class="mt-3 font-display text-3xl">Publication</h3>
                    <p class="mt-2 text-sm text-ash">Journal article · 1998</p>
                    <p class="t-body mt-4">4px radius, stone hairline, linen surface. Only populated fields render.</p>
                </article>
                <article class="card card-ivory">
                    <p class="t-label text-bronze">.card.card-ivory</p>
                    <h3 class="mt-3 font-display text-3xl">Award</h3>
                    <p class="mt-2 text-sm text-ash">Ministry of Higher Education · 2004</p>
                    <p class="t-body mt-4">Ivory variant for use on linen backgrounds such as the admin area.</p>
                </article>
                <article class="card flex flex-col items-center justify-center text-center">
                    <p class="t-label text-bronze">Empty state</p>
                    <p class="mt-4 font-display text-2xl italic">Nothing has been published here yet.</p>
                </article>
            </div>
        </section>

        {{-- Rules and glow --}}
        <section id="rules" class="py-20" aria-labelledby="rules-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>05 · Rules and glow</p>
            <h2 id="rules-title" class="t-heading mt-3" data-rise>Brass rules and the glow</h2>
            <div class="mt-8 space-y-6" data-rise>
                <p class="t-label text-bronze">.rule-brass (static)</p>
                <hr class="rule-brass">
                <p class="t-label text-bronze">.rule-brass[data-rule] (draws in over {{ Motion::RULE_DURATION_MS }}ms when in view)</p>
                <hr class="rule-brass" data-rule>
            </div>
            <div class="glow-brass on-dark mt-10 rounded-card px-6 py-20 text-center text-champagne" data-rise>
                <p class="t-label text-brass">.glow-brass</p>
                <p class="t-quote mx-auto mt-4 max-w-xl">&ldquo;His lessons continue in every student he taught.&rdquo;</p>
                <p class="mt-4 text-sm text-champagne/80">The only gradient permitted: a soft brass radial glow on evergreen.</p>
            </div>
        </section>

        {{-- Mark --}}
        <section id="mark" class="py-20" aria-labelledby="mark-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>06 · Mark</p>
            <h2 id="mark-title" class="t-heading mt-3" data-rise>The MK mark</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card on-dark flex flex-wrap items-end gap-8 border-cypress bg-evergreen text-champagne">
                    @foreach ([96, 64, 48, 32, 24] as $size)
                        <div class="flex flex-col items-center gap-2"><x-brand.mark :size="$size" /><span class="font-mono text-xs text-champagne/70">{{ $size }}px</span></div>
                    @endforeach
                </div>
                <div class="card flex flex-wrap items-end gap-8">
                    @foreach ([96, 64, 48, 32, 24] as $size)
                        <div class="flex flex-col items-center gap-2"><x-brand.mark :size="$size" tone="evergreen" /><span class="font-mono text-xs text-ash">{{ $size }}px</span></div>
                    @endforeach
                </div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card on-dark flex flex-col items-center gap-6 border-cypress bg-evergreen text-center text-champagne">
                    <p class="t-label self-start text-brass">Stacked lockup · tone ivory</p>
                    <x-brand.lockup variant="stacked" />
                </div>
                <div class="card flex flex-col items-center gap-6 text-center">
                    <p class="t-label self-start text-bronze">Stacked lockup · tone evergreen</p>
                    <x-brand.lockup variant="stacked" tone="evergreen" />
                </div>
                <div class="card on-dark flex items-center gap-3 border-cypress bg-evergreen text-champagne">
                    <x-brand.lockup variant="horizontal" />
                    <p class="t-label ml-auto text-brass">Horizontal lockup</p>
                </div>
                <div class="card flex items-center gap-3">
                    <x-brand.lockup variant="horizontal" tone="evergreen" />
                    <p class="t-label ml-auto text-bronze">Horizontal lockup</p>
                </div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card card-ivory">
                    <p class="t-label text-bronze">&lt;x-section-header&gt; and &lt;x-empty-state&gt;</p>
                    <x-section-header class="mt-6" label="Chapter One — Early Years" title="A childhood among books" level="3">Optional intro text sits in the slot.</x-section-header>
                    <x-empty-state message="Nothing has been published here yet." />
                </div>
                <div class="card on-dark border-cypress bg-evergreen text-champagne">
                    <p class="t-label text-brass">On evergreen · tone ivory</p>
                    <x-section-header class="mt-6" label="Chapter Two — Khartoum" title="The first lectures" level="3" tone="ivory">Brass label and champagne intro on dark.</x-section-header>
                    <x-empty-state message="No tributes have been approved yet." tone="ivory" />
                </div>
            </div>
        </section>

        {{-- Photos --}}
        <section id="photos" class="py-20" aria-labelledby="photos-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>07 · Photography</p>
            <h2 id="photos-title" class="t-heading mt-3" data-rise>Image treatments</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-3" data-rise-group>
                <figure>
                    <img src="{{ $sampleSrc }}" alt="Sample scene, untreated" class="w-full rounded-card" width="400" height="300" loading="lazy">
                    <figcaption class="t-label mt-3 text-bronze">Untreated · reference</figcaption>
                </figure>
                <figure>
                    <img src="{{ $sampleSrc }}" alt="Sample scene in warm monochrome" class="photo-portrait w-full rounded-card" width="400" height="300" loading="lazy">
                    <figcaption class="t-label mt-3 text-bronze">.photo-portrait · Khartoum, 1974</figcaption>
                </figure>
                <figure>
                    <img src="{{ $sampleSrc }}" alt="Sample scene in evergreen-to-ivory duotone" class="photo-archive w-full rounded-card" width="400" height="300" loading="lazy">
                    <figcaption class="t-label mt-3 text-bronze">.photo-archive · Omdurman, 1962</figcaption>
                </figure>
            </div>
        </section>

        {{-- Motion --}}
        <section id="motion" class="py-20" aria-labelledby="motion-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>08 · Motion</p>
            <h2 id="motion-title" class="t-heading mt-3" data-rise>Unhurried and scroll-linked</h2>
            <p class="t-body mt-4 max-w-2xl text-ash" data-rise>Everything on this page already uses these patterns. Reload and scroll to watch them; turn on "reduce motion" in your OS to see content appear instantly instead.</p>
            <dl class="mt-8 grid gap-5 md:grid-cols-2" data-rise-group>
                @foreach ($motion as $behaviour)
                    <div class="card">
                        <dt class="font-mono text-sm text-bronze">[{{ $behaviour['attribute'] }}]</dt>
                        <dd class="t-body mt-2">{{ $behaviour['summary'] }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-10 grid gap-4 sm:grid-cols-3" data-rise-group>
                @foreach (['One', 'Two', 'Three', 'Four', 'Five', 'Six'] as $n)
                    <div class="card card-ivory text-center"><p class="font-display text-3xl">{{ $n }}</p><p class="t-label mt-1 text-bronze">staggered {{ Motion::STAGGER_MS }}ms</p></div>
                @endforeach
            </div>
            <p class="t-label mt-16 text-bronze">Self-hosted font files</p>
            <ul class="mt-2 flex flex-col gap-1">
                @foreach (Typography::files() as $file)
                    <li><code class="block break-all font-mono text-xs text-ash">{{ Typography::path($file['file']) }} · {{ $file['family'] }} {{ $file['style'] }} {{ $file['weight'] }}</code></li>
                @endforeach
            </ul>
        </section>

        {{-- Press typography --}}
        <section id="press" class="py-20" aria-labelledby="press-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>09 · Press typography</p>
            <h2 id="press-title" class="t-heading mt-3" data-rise>Typographic craft and ornament</h2>
            <p class="t-body mt-4 max-w-measure text-ash" data-rise>Restraint, not effects: OpenType figures and small caps, an {{ $typesetting['baseline'] }}px rhythm ({{ $typesetting['bodyLine'] }}px body lines), a measure of {{ $typesetting['measure']['min'] }}–{{ $typesetting['measure']['max'] }} characters, and hairline ornaments in brass.</p>

            <h3 class="mt-12 font-display text-3xl" data-rise>OpenType features</h3>
            <dl class="mt-6 grid gap-5 md:grid-cols-2" data-rise-group>
                @foreach ($typesetting['features'] as $key => $feature)
                    <div class="card card-ivory">
                        <dt class="t-label text-bronze">{{ $feature['context'] }}</dt>
                        <dd class="mt-2 font-mono text-xs text-ash">{{ $feature['css'] }}</dd>
                        @if ($feature['features'] !== [])
                            <dd class="mt-1 font-mono text-xs text-ash">{{ implode(' · ', $feature['features']) }}</dd>
                        @endif
                    </div>
                @endforeach
            </dl>
            <div class="mt-8 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card">
                    <p class="t-label text-bronze">Old-style figures in running text</p>
                    <p class="mt-3 font-display text-3xl">Taught from 1975 to 1992, 40 years in all.</p>
                    <p class="t-label mt-6 text-bronze">Lining tabular figures in stats</p>
                    <p class="figures-lining mt-3 font-display text-3xl">1975 · 1992 · 60+ · 40</p>
                </div>
                <div class="card">
                    <p class="t-label text-bronze">Small caps and ligatures</p>
                    <p class="mt-3 font-display text-3xl">Reports for the <span class="caps">FAO</span> and <span class="caps">UNESCO</span></p>
                    <p class="mt-3 font-display text-2xl">By <span class="smallcaps">Mohamed Osman Khidir</span></p>
                    <p class="mt-3 font-display text-3xl italic pull-quote">The strictest artist, the first office</p>
                    <p class="t-label mt-6 text-bronze">Hanken tabular figures</p>
                    <p class="mt-2 font-sans tabular-nums">1,111 · 2,048 · 9,999</p>
                </div>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>Typesetter</h3>
            <p class="t-body mt-2 max-w-measure text-ash" data-rise>App\Services\TypesetterService runs on database text as it renders: curly quotes, en dashes for ranges, non-breaking spaces before units, after titles and between initials, an ellipsis, small caps for capitals.</p>
            <div class="mt-6 overflow-x-auto" data-rise>
                <table class="w-full text-left text-base">
                    <thead><tr class="border-b border-stone"><th class="t-label py-3 pr-6 text-bronze">As written</th><th class="t-label py-3 text-bronze">As rendered</th></tr></thead>
                    <tbody>
                        @foreach ($typesetExamples as $example)
                            <tr class="border-b border-stone align-top">
                                <td class="py-4 pr-6 font-mono text-sm text-ash">{{ $example['before'] }}</td>
                                <td class="py-4 font-display text-xl">{!! $example['after'] !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>&lt;x-chapter-opening&gt; and &lt;x-epigraph&gt;</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="card card-ivory">
                    <x-chapter-opening :number="2" label="Khartoum" title="The first lectures" level="4">
                        <x-slot:epigraph>
                            <x-epigraph attribution="Sample attribution">Knowledge is the light we pass on.</x-epigraph>
                        </x-slot:epigraph>
                    </x-chapter-opening>
                </div>
                <div class="card on-dark border-cypress bg-evergreen text-champagne">
                    <x-chapter-opening :number="3" label="Legacy" title="His lessons continue" tone="ivory" level="4">
                        <x-slot:epigraph>
                            <x-epigraph attribution="Sample attribution" tone="ivory">His lessons continue in every student he taught.</x-epigraph>
                        </x-slot:epigraph>
                    </x-chapter-opening>
                </div>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>&lt;x-drop-cap&gt;</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="card card-ivory">
                    <x-drop-cap class="prose-bio"><p>Professor Khidir devoted his life to agricultural science and to the students who sought his guidance. Over several decades he supervised graduate students and published widely on crop physiology.</p><p>A second paragraph has no initial.</p></x-drop-cap>
                </div>
                <div class="card on-dark border-cypress bg-evergreen">
                    <x-drop-cap class="prose-bio !text-champagne" tone="ivory"><p>On evergreen the initial is set in champagne. Over several decades he supervised graduate students and published widely on crop physiology and soil fertility.</p></x-drop-cap>
                </div>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>&lt;x-ornament&gt;</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="card card-ivory">
                    @foreach ($ornaments as $type => $ornament)
                        <p class="t-label text-bronze">{{ $type }} · {{ $ornament['label'] }}</p>
                        <x-ornament :type="$type" class="!my-6" />
                    @endforeach
                </div>
                <div class="card on-dark border-cypress bg-evergreen text-champagne">
                    @foreach ($ornaments as $type => $ornament)
                        <p class="t-label text-brass">{{ $type }}</p>
                        <x-ornament :type="$type" class="!my-6" />
                    @endforeach
                </div>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>&lt;x-sidenote&gt;</h3>
            <div class="sidenote-host mt-6">
                <p class="t-body">Sidenotes sit in the right margin when the column is wide enough, like Tufte's,<x-sidenote :number="1">This is a sidenote. On a narrow screen it opens inline when the number is tapped.</x-sidenote> and open inline on a phone. The reference is a button described by its note.<x-sidenote :number="2">Without JavaScript the notes are always shown inline.</x-sidenote> Resize the window to see both forms.</p>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>&lt;x-folio&gt; and &lt;x-seal&gt;</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="card card-ivory">
                    <x-folio section="About" />
                    <x-seal />
                    <x-folio section="About" position="bottom" class="mt-10" />
                </div>
                <div class="card on-dark border-cypress bg-evergreen text-champagne">
                    <x-folio section="Tributes" tone="ivory" />
                    <x-seal tone="ivory" />
                    <x-folio section="Tributes" position="bottom" tone="ivory" class="mt-10" />
                </div>
            </div>
        </section>
        {{-- Components --}}
        <section id="components" class="py-20" aria-labelledby="components-title">
            <hr class="rule-brass mb-10" data-rule>
            <p class="t-label text-bronze" data-rise>10 · Components</p>
            <h2 id="components-title" class="t-heading mt-3" data-rise>Every component, with its usage note</h2>
            <p class="t-body mt-4 max-w-measure text-ash" data-rise>The catalogue below is App\Constants\Components; a test fails when a component in resources/views/components has no entry. Live examples first, then the full list with how each one is meant to be used.</p>

            <h3 class="mt-12 font-display text-3xl" data-rise>Openers, metadata and notices</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card card-ivory">
                    <p class="t-label text-bronze">&lt;x-chapter-opening&gt; opens every section page</p>
                    <x-chapter-opening class="mt-6" :number="4" label="Awards" title="Prizes and recognitions" level="4">The intro sits in the default slot.</x-chapter-opening>
                    <p class="t-label mt-10 text-bronze">&lt;x-meta&gt; and &lt;x-date&gt;</p>
                    <x-meta class="mt-3" :items="['Journal article', 'Khartoum', new \DateTimeImmutable('1983-03-12')]" />
                    <p class="mt-3 t-body">Last updated <x-date :date="new \DateTimeImmutable('2026-09-21')" class="smallcaps" /></p>
                    <p class="t-label mt-10 text-bronze">&lt;x-museum-label&gt;</p>
                    <x-museum-label class="mt-3" as="div" title="Gezira research station" meta="Wad Madani, 1972" note="With the first cohort of agronomy students." />
                </div>
                <div class="card card-ivory">
                    <p class="t-label text-bronze">&lt;x-alert&gt; · one per tone</p>
                    <div class="mt-4 flex flex-col gap-3">
                        <x-alert tone="cypress">The publication was saved and is now live.</x-alert>
                        <x-alert tone="bronze">Add alt text before publishing this photograph.</x-alert>
                        <x-alert tone="danger" role="alert">The publication could not be published: two images still need alt text.</x-alert>
                        <x-alert tone="ash" title="Draft">Only staff can see this item.</x-alert>
                    </div>
                    <p class="t-label mt-10 text-bronze">&lt;x-admin.status-badge&gt;</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-admin.status-badge tone="cypress" label="Published" />
                        <x-admin.status-badge tone="bronze" label="Pending review" />
                        <x-admin.status-badge tone="ash" label="Draft" />
                    </div>
                    <p class="t-label mt-10 text-bronze">&lt;x-skeleton&gt; · while results load</p>
                    <div class="mt-4"><x-skeleton :lines="3" /></div>
                </div>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>Buttons, filters and links</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2" data-rise-group>
                <div class="card card-ivory">
                    <p class="t-label text-bronze">&lt;x-button&gt; on light · secondary is the outline pill, quiet the text action</p>
                    <div class="mt-5 flex flex-wrap items-center gap-4">
                        <x-button type="button">Save</x-button>
                        <x-button type="button" variant="quiet">Back to the list</x-button>
                        <x-button href="#components">As a link</x-button>
                    </div>
                    <p class="t-label mt-10 text-bronze">&lt;x-filter-pills&gt;</p>
                    <x-filter-pills class="mt-2" label="Topics" :options="[['label' => 'agronomy', 'url' => '#components', 'active' => false], ['label' => 'soil fertility', 'url' => '#components', 'active' => true]]" all-url="#components" :all-active="false" all-label="All topics" />
                    <p class="t-label mt-10 text-bronze">&lt;x-external-link&gt; and &lt;x-file-link&gt;</p>
                    <p class="mt-3 flex flex-wrap gap-6 text-base"><x-external-link href="https://example.org/source" label="Source" /><span class="link-meta">Download · PDF · 2.1 MB</span></p>
                </div>
                <div class="card on-dark border-cypress bg-evergreen text-champagne">
                    <p class="t-label text-brass">&lt;x-button&gt; on evergreen</p>
                    <div class="mt-5 flex flex-wrap items-center gap-4">
                        <x-button type="button" variant="primary" on-dark>Leave a tribute</x-button>
                        <x-button type="button" variant="secondary" on-dark>Read his story</x-button>
                        <x-button type="button" variant="quiet">Cancel</x-button>
                    </div>
                    <p class="t-label mt-10 text-brass">&lt;x-site.breadcrumbs&gt; · tone ivory</p>
                    <x-site.breadcrumbs class="mt-3" tone="ivory" :trail="[['label' => 'Home', 'url' => '#', 'current' => false], ['label' => 'Pictures', 'url' => '#', 'current' => false], ['label' => 'In the field', 'url' => '#', 'current' => true]]" />
                    <p class="t-label mt-10 text-brass">&lt;x-admin.stat-card&gt; sits on ivory</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-admin.stat-card label="Tributes pending review" :count="3" tone="bronze" noun="tribute" />
                        <x-admin.stat-card label="Publications" :count="15" caption="published" />
                    </div>
                </div>
            </div>

            <h3 class="mt-12 font-display text-3xl" data-rise>The catalogue</h3>
            @foreach ($components as $groupKey => $group)
                <div class="mt-10" data-component-group="{{ $groupKey }}" data-rise>
                    <p class="t-label text-bronze">{{ $group['label'] }}</p>
                    <hr class="rule-brass mt-4" data-rule>
                    <dl class="divide-y divide-stone">
                        @foreach ($group['components'] as $component)
                            <div class="grid gap-2 py-6 md:grid-cols-[14rem_minmax(0,1fr)] md:gap-8" data-component="{{ $component['tag'] }}">
                                <dt><code class="font-mono text-sm text-evergreen">&lt;{{ $component['tag'] }}&gt;</code></dt>
                                <dd class="flex flex-col gap-1">
                                    <span class="text-base font-normal text-evergreen">{{ $component['purpose'] }}</span>
                                    <span class="text-base font-light text-ash">{{ $component['usage'] }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </section>
    </div>
</x-layouts.public>
