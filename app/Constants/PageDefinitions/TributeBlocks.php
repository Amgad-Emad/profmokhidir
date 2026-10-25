<?php

declare(strict_types=1);

namespace App\Constants\PageDefinitions;

use App\Constants\Sections;
use App\Constants\Taglines;
use App\Constants\TributeForm;
use App\Support\PageBlockDefinition as Block;

/**
 * The editable blocks of the Tributes pages: the book of remembrance, the
 * two-step tribute form and the thank-you page. Defaults are the Taglines and
 * TributeForm copy; the share image falls back to the static tributes card.
 */
final class TributeBlocks
{
    public const string SECTION_OPENING = 'Opening';

    public const string SECTION_BOOK = 'The book of remembrance';

    public const string SECTION_FORM = 'The tribute form';

    public const string SECTION_PREVIEW = 'Preview step';

    public const string SECTION_THANKS = 'Thank-you page';

    public const string SECTION_SEO = 'Search & sharing';

    public const string SHARE_LABEL = 'Share card';

    public const string EMPTY = 'No tributes have been published yet. Yours could be the first.';

    public const string EMPTY_FILTERED = 'No tributes from :relationship have been published yet.';

    public const string PREVIEW_BUTTON = 'Preview your tribute';

    public const string BACK_LABEL = 'Back to tributes';

    public const string SEND_BUTTON = 'Send tribute';

    public const string CHANGE_BUTTON = 'Change something';

    public const string HOME_BUTTON = 'Home';

    /**
     * @return list<Block>
     */
    public static function definitions(): array
    {
        $section = Sections::definitions()[Sections::TRIBUTES];
        $fields = [];

        foreach (TributeForm::fields() as $key => $field) {
            $fields[] = ['key' => $key, 'label' => $field['label'], 'hint' => $field['hint']];
        }

        return [
            Block::shortText('label', 'Label', self::SECTION_OPENING, Taglines::TRIBUTES_LABEL, 30),
            Block::shortText('heading', 'Page heading', self::SECTION_OPENING, Taglines::TRIBUTES_HEADING, 140, hint: 'Set in the quote style.'),
            Block::shortText('title', 'Page name', self::SECTION_OPENING, $section['title'], 60, hint: 'Its name in breadcrumbs, menus, search results and the running head.'),
            Block::shortText('intro', 'Introduction', self::SECTION_OPENING, $section['summary'], 240, hint: 'Under the heading. Also used in menus, cards and as the search description.'),
            Block::link('button_tribute', 'Button: Leave a tribute', self::SECTION_OPENING, Taglines::LEAVE_TRIBUTE, 'tributes.create', 30, hint: 'Also repeated at the foot of the book.'),

            Block::shortText('featured_label', 'Label above the featured tribute', self::SECTION_BOOK, Taglines::TRIBUTES_FEATURED_LABEL, 30),
            Block::shortText('book_label', 'Label above the book', self::SECTION_BOOK, Taglines::TRIBUTES_BOOK_LABEL, 40),
            Block::shortText('share_label', 'Link: Share card', self::SECTION_BOOK, self::SHARE_LABEL, 30),
            Block::shortText('empty', 'Line while no tribute is published', self::SECTION_BOOK, self::EMPTY, 160),
            Block::shortText('empty_filtered', 'Line when a relationship has none', self::SECTION_BOOK, self::EMPTY_FILTERED, 160, tokens: [':relationship' => 'the relationship, for example students']),

            Block::shortText('form_title', 'Form heading', self::SECTION_FORM, Taglines::LEAVE_TRIBUTE, 60),
            Block::shortText('form_intro', 'Form introduction', self::SECTION_FORM, TributeForm::INTRO, 240),
            Block::list('fields', 'Field labels and hints', self::SECTION_FORM, $fields, [
                'key' => Block::fixedField('Field'),
                'label' => Block::field('Label', 40),
                'hint' => Block::field('Hint', 160),
            ], 'Field', fixedItems: true, sortable: false),
            Block::shortText('preview_button', 'Button: Preview your tribute', self::SECTION_FORM, self::PREVIEW_BUTTON, 30),
            Block::shortText('back_label', 'Button: Back to tributes', self::SECTION_FORM, self::BACK_LABEL, 30, hint: 'Also on the thank-you page.'),

            Block::shortText('preview_intro', 'Preview introduction', self::SECTION_PREVIEW, TributeForm::PREVIEW_INTRO, 240),
            Block::shortText('consent', 'Checkbox: consent to publish', self::SECTION_PREVIEW, TributeForm::CONSENT, 240),
            Block::shortText('send_button', 'Button: Send tribute', self::SECTION_PREVIEW, self::SEND_BUTTON, 30),
            Block::shortText('change_button', 'Button: Change something', self::SECTION_PREVIEW, self::CHANGE_BUTTON, 30),

            Block::shortText('thanks', 'Thank-you message', self::SECTION_THANKS, Taglines::TRIBUTE_THANKS, 200),
            Block::shortText('home_button', 'Button: Home', self::SECTION_THANKS, self::HOME_BUTTON, 30),

            Block::seo(self::SECTION_SEO, hint: 'Leave the title empty to use the page name, the description empty to use the introduction, and the image empty to use the tributes share card.'),
        ];
    }
}
