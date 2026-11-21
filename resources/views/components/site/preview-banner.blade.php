{{--
    Shown on a public page opened from a signed preview link (App\Services\PreviewService): a quiet
    bar along the bottom saying this is a draft visitors cannot see yet. Plain markup, no script.
    The same bar names the colour theme an admin is previewing from Admin › Appearance (only they see
    it, App\Services\ColourThemeService::previewing), with a link that ends the preview.
    Staff opening a section hidden in Site settings are told visitors cannot see it.
--}}
@inject('preview', 'App\Services\PreviewService')
@inject('colourThemes', 'App\Services\ColourThemeService')
@inject('sectionVisibility', 'App\Services\SectionVisibilityService')
@php($colourPreview = $colourThemes->previewing(auth()->user()))
@php($hiddenSection = $sectionVisibility->sectionForRoute(request()->route()?->getName()))
@php($hiddenSection = $hiddenSection !== null && ! $sectionVisibility->isVisible($hiddenSection) ? $hiddenSection : null)
@if ($preview->active())
    <div class="preview-banner on-dark" role="status" data-preview-banner>
        <span class="t-label preview-banner__label">{{ __(\App\Constants\Preview::BANNER_LABEL) }}</span>
        <span class="preview-banner__text">{{ __(\App\Constants\Preview::BANNER_TEXT) }}</span>
    </div>
@elseif ($hiddenSection !== null)
    <div class="preview-banner on-dark" role="status" data-hidden-section-banner>
        <span class="t-label preview-banner__label">{{ __('Hidden section') }}</span>
        <span class="preview-banner__text">{{ __('Visitors cannot see this section. Show it again in Site settings.') }}</span>
    </div>
@elseif ($colourPreview !== null)
    <div class="preview-banner on-dark" role="status" data-colour-preview-banner>
        <span class="t-label preview-banner__label">{{ __('Colour preview') }}</span>
        <span class="preview-banner__text">{{ __('You are seeing the draft colours of “:name”. Only you can see them.', ['name' => $colourPreview->name]) }}</span>
        <a class="preview-banner__text underline" href="{{ route(\App\Constants\Appearance::PREVIEW_END_ROUTE, ['to' => request()->getRequestUri()]) }}">{{ __('End preview') }}</a>
    </div>
@endif
