{{--
    The mail theme (inlined into every email). A Blade view rather than plain CSS so the
    language of the email sets the text alignment and the font stack: Arabic emails read
    right to left in an Arabic-capable stack, English ones keep the brand's sans. No web
    fonts are loaded in email; the stack falls back to what the reader has.
    Colours come from the active colour theme's light mode, so the accent of every email follows it.
--}}
@php
    $rtl = \App\Constants\Locales::isRtl(app()->getLocale());
    // Colours: the light mode of the active colour theme (Admin › Appearance; the brand by default), never raw values.
    $c = app(\App\Services\ColourThemeService::class)->activePalette()['light'];
    $flat = fn (string $token): string => \App\Support\Colour::over($c[$token], $c['surface']);
    $align = $rtl ? 'right' : 'left';
    $fonts = $rtl
        ? "'IBM Plex Sans Arabic', 'Hanken Grotesk', Arial, sans-serif"
        : "'Hanken Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
/* Base */

body,
body *:not(html):not(style):not(br):not(tr):not(code) {
    box-sizing: border-box;
    font-family: {!! $fonts !!}, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
    position: relative;
}

body {
    -webkit-text-size-adjust: none;
    background-color: {{ $c['surface'] }};
    color: {{ $flat('ink-muted') }};
    direction: {{ $rtl ? 'rtl' : 'ltr' }};
    height: 100%;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    width: 100% !important;
}

p,
ul,
ol,
blockquote {
    line-height: 1.4;
    text-align: {{ $align }};
}

a {
    color: {{ $c['accent-text'] }};
}

a img {
    border: none;
}

/* Typography */

h1 {
    color: {{ $c['ink-strong'] }};
    font-size: 18px;
    font-weight: bold;
    margin-top: 0;
    text-align: {{ $align }};
}

h2 {
    font-size: 16px;
    font-weight: bold;
    margin-top: 0;
    text-align: {{ $align }};
}

h3 {
    font-size: 14px;
    font-weight: bold;
    margin-top: 0;
    text-align: {{ $align }};
}

p {
    font-size: 16px;
    line-height: 1.5em;
    margin-top: 0;
    text-align: {{ $align }};
}

p.sub {
    font-size: 12px;
}

img {
    max-width: 100%;
}

/* Layout */

.wrapper {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: {{ $c['surface-sunken'] }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 0;
    padding: 0;
    width: 100%;
}

/* Header */

.header {
    padding: 25px 0;
    text-align: center;
}

.header a {
    color: {{ $c['ink-strong'] }};
    font-size: 19px;
    font-weight: bold;
    text-decoration: none;
}

/* Logo */

.logo {
    height: 75px;
    margin-top: 15px;
    margin-bottom: 10px;
    max-height: 75px;
    width: 75px;
}

/* Body */

.body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: {{ $c['surface-sunken'] }};
    border-bottom: 1px solid {{ $c['surface-sunken'] }};
    border-top: 1px solid {{ $c['surface-sunken'] }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    background-color: {{ $c['surface'] }};
    border-color: {{ $flat('rule') }};
    border-radius: 4px;
    border-width: 1px;
    border: 1px solid {{ $flat('rule') }};
    margin: 0 auto;
    padding: 0;
    width: 570px;
}

.inner-body a {
    word-break: break-all;
}

/* Subcopy */

.subcopy {
    border-top: 1px solid {{ $flat('rule') }};
    margin-top: 25px;
    padding-top: 25px;
}

.subcopy p {
    font-size: 14px;
}

/* Footer */

.footer {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    margin: 0 auto;
    padding: 0;
    text-align: center;
    width: 570px;
}

.footer p {
    color: {{ $flat('ink-quiet') }};
    font-size: 12px;
    text-align: center;
}

.footer a {
    color: {{ $flat('ink-quiet') }};
    text-decoration: underline;
}

/* Tables */

.table table {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    width: 100%;
}

.table th {
    border-bottom: 1px solid {{ $flat('rule') }};
    margin: 0;
    padding-bottom: 8px;
}

.table td {
    color: {{ $flat('ink-muted') }};
    font-size: 15px;
    line-height: 18px;
    margin: 0;
    padding: 10px 0;
}

.content-cell {
    max-width: 100vw;
    padding: 32px;
}

/* Buttons */

.action {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    padding: 0;
    text-align: center;
    width: 100%;
    float: unset;
}

.button {
    -webkit-text-size-adjust: none;
    border-radius: 4px;
    color: {{ $c['fill-ink'] }};
    display: inline-block;
    overflow: hidden;
    text-decoration: none;
}

.button-blue,
.button-primary {
    background-color: {{ $c['ink-strong'] }};
    border-bottom: 8px solid {{ $c['fill'] }};
    border-left: 18px solid {{ $c['fill'] }};
    border-right: 18px solid {{ $c['fill'] }};
    border-top: 8px solid {{ $c['fill'] }};
}

.button-green,
.button-success {
    background-color: {{ $c['success-tone'] }};
    border-bottom: 8px solid {{ $c['success-tone'] }};
    border-left: 18px solid {{ $c['success-tone'] }};
    border-right: 18px solid {{ $c['success-tone'] }};
    border-top: 8px solid {{ $c['success-tone'] }};
}

.button-red,
.button-error {
    background-color: {{ $c['danger-surface'] }};
    border-bottom: 8px solid {{ $c['danger-surface'] }};
    border-left: 18px solid {{ $c['danger-surface'] }};
    border-right: 18px solid {{ $c['danger-surface'] }};
    border-top: 8px solid {{ $c['danger-surface'] }};
}

/* Panels */

.panel {
    border-left: {{ $c['accent-line'] }} solid 4px;
    margin: 21px 0;
}

.panel-content {
    background-color: {{ $c['surface-sunken'] }};
    color: {{ $flat('ink-muted') }};
    padding: 16px;
}

.panel-content p {
    color: {{ $flat('ink-muted') }};
}

.panel-item {
    padding: 0;
}

.panel-item p:last-of-type {
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Utilities */

.break-all {
    word-break: break-all;
}
