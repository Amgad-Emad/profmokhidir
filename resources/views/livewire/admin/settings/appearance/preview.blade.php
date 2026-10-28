{{--
    The live preview: a real page of the site in a frame drawn at desktop width and scaled to the pane,
    in the mode being edited. resources/js/admin/appearance.js writes the unsaved colours into the frame
    (the state element below changes with every edit), so it follows each change before it is saved.
    The frame is inert (a picture of the page); "Preview on site" opens the saved draft for real.
--}}
@use('App\Constants\Appearance')
<div class="appearance-preview" x-data="appearancePreview" data-appearance-preview>
    <div x-ref="state" hidden data-css="{{ $previewCss }}" data-mode="{{ $mode }}" data-url="{{ $previewUrls[$previewPage] }}"></div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="t-label text-accent-text">{{ __('Live preview') }} · {{ \App\Constants\Theme::from($mode)->label() }}</h3>
        <div class="appearance-preview__pages" role="group" aria-label="{{ __('Page shown in the preview') }}">
            @foreach (Appearance::PREVIEW_PAGES as $key => $page)
                <x-button type="button" size="sm" :variant="$previewPage === $key ? 'primary' : 'quiet'" wire:click="setPreviewPage('{{ $key }}')" aria-pressed="{{ $previewPage === $key ? 'true' : 'false' }}" data-preview-page="{{ $key }}">{{ __($page['label']) }}</x-button>
            @endforeach
        </div>
    </div>
    <div class="appearance-preview__viewport" x-ref="viewport" wire:ignore>
        <iframe x-ref="frame" class="appearance-preview__frame" title="{{ __('Preview of the site with these colours') }}" inert tabindex="-1"></iframe>
    </div>
</div>
