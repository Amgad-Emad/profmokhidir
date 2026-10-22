<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The shared form-control system (resources/views/components/form, styles in
 * resources/css/forms.css, behaviour in resources/js/forms.js): the wording every
 * field uses, the sizes the stylesheet is built on and the rich text toolbar.
 * The states themselves are listed in App\Constants\FormStates.
 */
final class FormControls
{
    /** Shown in the hint line of a required field (never a lone asterisk). */
    public const string REQUIRED = 'Required';

    /** Shown in the hint line of a field that may be left blank. */
    public const string OPTIONAL = 'Optional';

    /** Joins the requirement word and the hint ("Required · Two or three sentences."). */
    public const string HINT_SEPARATOR = ' · ';

    /**
     * The placeholder of a field that shows none: a no-break space, so the browser still
     * reports :placeholder-shown while it is empty and the stylesheet can tell a filled
     * field from an empty one without a script (a plain space is trimmed away by Blade).
     */
    public const string BLANK_PLACEHOLDER = "\u{00A0}";

    /** Minimum height of every single-line control, and its text size (16px or more so iOS never zooms). */
    public const int MIN_HEIGHT_PX = 48;

    public const int TEXT_SIZE_PX = 18;

    /** The focus ring: 2px, offset 2px, around the control only. */
    public const int FOCUS_RING_PX = 2;

    public const int FOCUS_OFFSET_PX = 2;

    /** Year inputs (and ContentRequest::yearRule). */
    public const int YEAR_MIN = 1900;

    public const int YEAR_DIGITS = 4;

    /** Characters counted before the counter starts showing on a field with a limit, as a share of the limit. */
    public const float COUNTER_NEAR_LIMIT = 0.8;

    public const string COUNTER_FORMAT = ':count / :max characters';

    public const string WORD_COUNT_SINGULAR = ':count word';

    public const string WORD_COUNT_PLURAL = ':count words';

    public const string ERROR_SUMMARY_TITLE = 'There is a problem with this form';

    public const string ERROR_SUMMARY_INTRO = 'Correct the fields below, then save again.';

    // ---------------------------------------------------------------- browser messages (blur validation)

    public const string MESSAGE_REQUIRED = 'Enter :label.';

    public const string MESSAGE_CHOOSE = 'Choose :label.';

    public const string MESSAGE_CHECK = 'Tick this box to continue.';

    public const string MESSAGE_EMAIL = 'Enter an email address in the form name@example.com.';

    public const string MESSAGE_URL = 'Enter a full web address starting with https://.';

    public const string MESSAGE_TOO_LONG = 'Shorten this to :max characters or fewer.';

    public const string MESSAGE_TOO_SHORT = 'Use at least :min characters.';

    public const string MESSAGE_NUMBER = 'Enter a whole number.';

    public const string MESSAGE_RANGE = 'Enter a number between :min and :max.';

    public const string MESSAGE_YEAR = 'Enter a year between :min and :max.';

    public const string MESSAGE_DATE = 'Enter a date as day, month and year.';

    public const string MESSAGE_CODE = 'Enter the :length-digit code from your app.';

    // ---------------------------------------------------------------- password

    public const string SHOW_PASSWORD = 'Show password';

    public const string CAPS_LOCK = 'Caps Lock is on.';

    // ---------------------------------------------------------------- choosers

    public const string SELECT_PLACEHOLDER = 'Choose one';

    public const string COMBOBOX_NO_MATCHES = 'No matches. Try fewer letters.';

    public const string COMBOBOX_RESULTS = ':count options';

    public const string TAGS_ADD_HINT = 'Press Enter or a comma after each one.';

    public const string TAGS_REMOVE = 'Remove :tag';

    public const string TAGS_ADDED = ':tags added.';

    public const string TAGS_REMOVED = ':tag removed.';

    public const string TAGS_EDITING = ':tag is back in the field for editing.';

    public const string TOGGLE_ON = 'On';

    public const string TOGGLE_OFF = 'Off';

    // ---------------------------------------------------------------- uploads

    public const string UPLOAD_DROP = 'Drag a file here, or';

    public const string UPLOAD_DROP_IMAGES = 'Drag images here, or';

    public const string UPLOAD_CHOOSE = 'choose a file';

    public const string UPLOAD_CHOOSE_IMAGES = 'choose images';

    public const string UPLOAD_PROGRESS = 'Uploading… :percent%';

    public const string UPLOAD_DONE = 'Uploaded. Save to keep it.';

    public const string UPLOAD_FAILED = 'The upload did not finish. Check your connection and choose the file again.';

    public const string UPLOAD_REPLACE = 'Replace';

    public const string UPLOAD_REMOVE = 'Remove';

    public const string UPLOAD_ALT_PROMPT = 'Describe what the image shows for people who cannot see it.';

    // ---------------------------------------------------------------- rich text editor

    public const string EDITOR_TOOLBAR = 'Formatting';

    public const string LINK_DIALOG_TITLE = 'Add a link';

    public const string LINK_URL_LABEL = 'Web address';

    public const string LINK_URL_HINT = 'A full address starting with https://, or mailto: for an email.';

    public const string LINK_INVALID = 'Enter a full web address starting with https:// or mailto:.';

    public const string LINK_APPLY = 'Apply link';

    public const string LINK_REMOVE = 'Remove link';

    public const string LINK_CANCEL = 'Cancel';

    /** Protocols the editor accepts for links (App\Support\RichText sanitises again on save). */
    public const array LINK_PROTOCOLS = ['https', 'http', 'mailto'];

    /**
     * The rich text toolbar in order: command => label, icon (x-icon), shortcut
     * (Tiptap notation; "Mod" is Ctrl, or ⌘ on a Mac) and the group it sits in.
     *
     * @return array<string, array{label: string, icon: string, shortcut: string, group: string}>
     */
    public static function editorTools(): array
    {
        return [
            'heading2' => ['label' => 'Heading', 'icon' => 'heading', 'shortcut' => 'Mod-Alt-2', 'group' => 'block'],
            'heading3' => ['label' => 'Subheading', 'icon' => 'subheading', 'shortcut' => 'Mod-Alt-3', 'group' => 'block'],
            'bold' => ['label' => 'Bold', 'icon' => 'bold', 'shortcut' => 'Mod-B', 'group' => 'inline'],
            'italic' => ['label' => 'Italic', 'icon' => 'italic', 'shortcut' => 'Mod-I', 'group' => 'inline'],
            'bulletList' => ['label' => 'Bulleted list', 'icon' => 'list-bullet', 'shortcut' => 'Mod-Shift-8', 'group' => 'list'],
            'orderedList' => ['label' => 'Numbered list', 'icon' => 'list-numbered', 'shortcut' => 'Mod-Shift-7', 'group' => 'list'],
            'blockquote' => ['label' => 'Quote', 'icon' => 'quote', 'shortcut' => 'Mod-Shift-B', 'group' => 'list'],
            'link' => ['label' => 'Link', 'icon' => 'link', 'shortcut' => 'Mod-K', 'group' => 'link'],
            'undo' => ['label' => 'Undo', 'icon' => 'undo', 'shortcut' => 'Mod-Z', 'group' => 'history'],
            'redo' => ['label' => 'Redo', 'icon' => 'redo', 'shortcut' => 'Mod-Shift-Z', 'group' => 'history'],
        ];
    }

    /**
     * A Tiptap shortcut as people read it: "Mod-Shift-8" → "Ctrl+Shift+8".
     */
    public static function shortcutLabel(string $shortcut): string
    {
        return str_replace(['Mod', '-'], ['Ctrl', '+'], $shortcut);
    }

    /** The latest year a year field accepts (next year, so forthcoming items fit). */
    public static function yearMax(): int
    {
        return (int) now()->addYear()->format('Y');
    }

    public static function yearMessage(?int $min = null, ?int $max = null): string
    {
        return strtr(self::MESSAGE_YEAR, [':min' => (string) ($min ?? self::YEAR_MIN), ':max' => (string) ($max ?? self::yearMax())]);
    }

    /**
     * The browser-side messages forms.js shows on blur, keyed by the
     * ValidityState flag they explain (placeholders filled per field).
     *
     * @return array<string, string>
     */
    public static function browserMessages(): array
    {
        return [
            'valueMissing' => self::MESSAGE_REQUIRED,
            'valueMissingChoice' => self::MESSAGE_CHOOSE,
            'valueMissingCheck' => self::MESSAGE_CHECK,
            'typeMismatchEmail' => self::MESSAGE_EMAIL,
            'typeMismatchUrl' => self::MESSAGE_URL,
            'tooLong' => self::MESSAGE_TOO_LONG,
            'tooShort' => self::MESSAGE_TOO_SHORT,
            'badInput' => self::MESSAGE_NUMBER,
            'stepMismatch' => self::MESSAGE_NUMBER,
            'range' => self::MESSAGE_RANGE,
            'year' => self::MESSAGE_YEAR,
            'date' => self::MESSAGE_DATE,
        ];
    }
}
