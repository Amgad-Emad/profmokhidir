{{--
    Version history of the record being edited (a drawer): every save, publish and autosave, newest
    first, with who and when, and "Load this version" to bring an earlier one back into the form.
    Nothing changes until the editor saves.
--}}
@props(['editor', 'show' => false])
<x-admin.drawer :show="$show" :title="__('Version history')" close="toggleHistory" id="history" :description="__('Each save keeps a copy. Load one to bring it back into the form; it is kept only when you save.')">
    @if ($editor['revisions']->isEmpty())
        <x-empty-state :message="__('No earlier versions yet.')" class="py-8" />
    @else
        <x-admin.data-table :table="app(\App\Services\RevisionService::class)->table($editor['revisions'])" data-revisions />
    @endif
</x-admin.drawer>
