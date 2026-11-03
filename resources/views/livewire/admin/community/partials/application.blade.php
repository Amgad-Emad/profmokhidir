{{-- Under an applicant's row: what they wrote when applying and how they were reviewed (App\Livewire\Admin\Community\Applications). --}}
<dl class="grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2" data-application>
    <div class="sm:col-span-2">
        <dt class="t-label text-accent-text">{{ __('Reason for joining') }}</dt>
        <dd class="mt-1 whitespace-pre-line font-normal text-ink" dir="auto">{{ $user->reason_for_joining ?: __('Not given') }}</dd>
    </div>
    <div>
        <dt class="t-label text-accent-text">{{ __('Affiliation') }}</dt>
        <dd class="mt-1 font-normal text-ink" dir="auto">{{ $user->affiliation ?: __('Not given') }}</dd>
    </div>
    <div>
        <dt class="t-label text-accent-text">{{ __('Applied') }}</dt>
        <dd class="mt-1 font-normal text-ink"><x-date :date="$user->created_at" /></dd>
    </div>
    @if ($user->reviewed_at)
        <div>
            <dt class="t-label text-accent-text">{{ __('Reviewed') }}</dt>
            <dd class="mt-1 font-normal text-ink"><x-date :date="$user->reviewed_at" />{{ $user->reviewer ? ' '.__('by :name', ['name' => $user->reviewer->name]) : '' }}</dd>
        </div>
    @endif
    @if ($user->review_note)
        <div class="sm:col-span-2">
            <dt class="t-label text-accent-text">{{ __('Note') }}</dt>
            <dd class="mt-1 whitespace-pre-line font-normal text-ink" dir="auto">{{ $user->review_note }}</dd>
        </div>
    @endif
</dl>
