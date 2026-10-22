{{--
    Version history of the record being edited (a drawer): every save, publish and autosave, newest
    first, with who and when, and "Load this version" to bring an earlier one back into the form.
    Nothing changes until the editor saves.
--}}
@props(['editor', 'show' => false])
<x-admin.drawer :show="$show" title="Version history" close="toggleHistory" id="history" description="Each save keeps a copy. Load one to bring it back into the form; it is kept only when you save.">
    @if ($editor['revisions']->isEmpty())
        <x-empty-state message="No earlier versions yet." class="py-8" />
    @else
        <ol class="revision-list" data-revisions>
            @foreach ($editor['revisions'] as $revision)
                <li class="revision-list__item" wire:key="revision-{{ $revision->getKey() }}">
                    <div>
                        <p class="font-medium text-ink">{{ $revision->kind->label() }}{{ $loop->first ? ' · latest' : '' }}</p>
                        <p class="text-sm font-light text-ink-muted">
                            <time datetime="{{ $revision->created_at->toIso8601String() }}">{{ $revision->created_at->format(\App\Constants\DateFormats::LONG.', H:i') }}</time>
                            · {{ $revision->created_at->diffForHumans() }}{{ $revision->user ? ' · '.$revision->user->name : '' }}
                        </p>
                    </div>
                    @unless ($loop->first)
                        <button type="button" class="save-bar__link" wire:click="restoreRevision({{ $revision->getKey() }})" data-restore-revision="{{ $revision->getKey() }}">Load this version<span class="sr-only"> from {{ $revision->created_at->format(\App\Constants\DateFormats::LONG.', H:i') }}</span></button>
                    @endunless
                </li>
            @endforeach
        </ol>
    @endif
</x-admin.drawer>
