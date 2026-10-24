{{--
    The settings of the first-party analytics. Printed only while analytics is switched on, for
    visitors who are not signed-in staff and have not asked not to be tracked (Do Not Track, Global
    Privacy Control), and never on excluded paths. Used by the public layout only, so the dashboard,
    the chat and the sign-in screens never carry it. resources/js/app.js imports the script
    (resources/js/analytics.js) after the page has loaded, and only when this tag is present.
--}}
@inject('analytics', 'App\Services\AnalyticsService')
@if ($analytics->shouldLoadScript(request()))
    <meta name="analytics" content="{{ $analytics->endpoint() }}">
@endif
