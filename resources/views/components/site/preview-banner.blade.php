{{--
    Shown on a public page opened from a signed preview link (App\Services\PreviewService): a quiet
    bar along the bottom saying this is a draft visitors cannot see yet. Plain markup, no script.
--}}
@inject('preview', 'App\Services\PreviewService')
@if ($preview->active())
    <div class="preview-banner on-dark" role="status" data-preview-banner>
        <span class="t-label preview-banner__label">{{ \App\Constants\Preview::BANNER_LABEL }}</span>
        <span class="preview-banner__text">{{ \App\Constants\Preview::BANNER_TEXT }}</span>
    </div>
@endif
