{{--
    The translations of the interface strings a layout's scripts use (App\Constants\ScriptStrings),
    printed as window.i18n before the scripts load. English pages print nothing: the scripts fall
    back to the English source (resources/js/i18n.js).
--}}
@use('App\Constants\Locales')
@use('App\Constants\ScriptStrings')
@props(['bundles' => [ScriptStrings::SITE]])
@if (app()->getLocale() !== Locales::DEFAULT)
    <script>window.{{ ScriptStrings::GLOBAL }} = @json(ScriptStrings::translations($bundles), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);</script>
@endif
