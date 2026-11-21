<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Colour theme editing in Admin › Appearance
    |--------------------------------------------------------------------------
    |
    | Off by the owner's decision (21 November 2026): the dashboard shows the
    | colours the site uses now and nothing else. With it off, the templates,
    | the theme editor, apply, duplicate, import, export, delete and restore
    | are hidden, and the component refuses every one of those actions on the
    | server. Set APPEARANCE_EDITING=true to bring the editor back.
    |
    */

    'editing' => (bool) env('APPEARANCE_EDITING', false),

];
