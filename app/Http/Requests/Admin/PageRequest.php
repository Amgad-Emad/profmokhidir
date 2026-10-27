<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\PageBlocks;
use App\Constants\PageBlockType;
use App\Constants\PageLinks;
use App\Constants\Pages;
use App\Constants\Uploads;
use App\Rules\PlainTextMax;
use App\Support\PageBlockDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * The edit screen of one page (Admin › Pages): rules derived from the page's
 * block definitions, so every limit that protects the layout is enforced on
 * save. Empty is allowed everywhere: an emptied required block takes its
 * default (PageBlockValue). Images in view need their details before the page
 * can be published. The Livewire screen authorises through PagePolicy.
 */
class PageRequest extends FormRequest
{
    private string $page = Pages::HOME;

    public function forPage(string $page): static
    {
        if (! Pages::isValid($page)) {
            throw new InvalidArgumentException(sprintf('Unknown page "%s".', $page));
        }

        $this->page = $page;

        return $this;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (PageBlocks::for($this->page) as $key => $definition) {
            $rules += $this->blockRules($key, $definition);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (PageBlocks::for($this->page) as $key => $definition) {
            // Built from translated parts, in the editor's language.
            $label = mb_strtolower(__($definition->label));

            $attributes[$key] = $label;
            $attributes[$key.'.label'] = __(':field label', ['field' => $label]);
            $attributes[$key.'.url'] = __(':field web address', ['field' => $label]);
            $attributes[$key.'.route'] = __(':field destination', ['field' => $label]);
            $attributes[$key.'.alt_text'] = __('alt text');
            $attributes[$key.'.caption'] = __('caption');
            $attributes[$key.'.place'] = __('place');
            $attributes[$key.'.year'] = __('year');
            $attributes[$key.'.title'] = __('search title');
            $attributes[$key.'.description'] = __('search description');

            foreach ($definition->editableFields() as $field => $spec) {
                $attributes[$key.'.*.'.$field] = mb_strtolower(__(':item: :field', ['item' => __((string) $definition->itemLabel), 'field' => __($spec['label'])]));
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.alt_text.required_with' => __('Describe the image for people who cannot see it.'),
            '*.caption.required_with' => __('Every photograph needs a caption.'),
            '*.place.required_with' => __('Every photograph needs the place it was taken.'),
            '*.year.required_with' => __('Every photograph needs the year it was taken.'),
            '*.url.url' => __('Enter a full web address beginning with https://.'),
        ];
    }

    /**
     * Rules for the Arabic tab (App\Support\PageBlockValue::arabicForm()):
     * words only, every one optional, with the English limits.
     *
     * @return array<string, list<mixed>>
     */
    public function arabicRules(): array
    {
        $rules = [];
        $text = fn (int $max): array => ['nullable', 'string', 'max:'.$max];

        foreach (PageBlocks::for($this->page) as $key => $definition) {
            $rules += match ($definition->type) {
                PageBlockType::ShortText => [$key => $text((int) $definition->max)],
                PageBlockType::LongText => [$key => ['nullable', 'string', 'max:'.($definition->max * 4), new PlainTextMax((int) $definition->max)]],
                PageBlockType::Link => [$key => ['array'], $key.'.label' => $text((int) $definition->max)],
                PageBlockType::Image => [
                    $key => ['array'],
                    $key.'.alt_text' => $text(PageBlockDefinition::IMAGE_TEXT_MAX),
                    $key.'.caption' => $text(PageBlockDefinition::IMAGE_TEXT_MAX),
                    $key.'.place' => $text(PageBlockDefinition::IMAGE_TEXT_MAX),
                ],
                PageBlockType::Seo => [
                    $key => ['array'],
                    $key.'.title' => $text(PageBlockDefinition::SEO_TITLE_MAX),
                    $key.'.description' => $text(PageBlockDefinition::SEO_DESCRIPTION_MAX),
                    $key.'.alt_text' => $text(PageBlockDefinition::IMAGE_TEXT_MAX),
                ],
                PageBlockType::List => [$key => ['array'], ...array_combine(
                    array_map(fn (string $field): string => $key.'.*.'.$field, array_keys($this->textFields($definition))),
                    array_map(fn (array $spec): array => $text((int) $spec['max']), array_values($this->textFields($definition))),
                )],
            };
        }

        return $rules;
    }

    /**
     * Attribute names for the Arabic tab ("Arabic hero heading").
     *
     * @return array<string, string>
     */
    public function arabicAttributes(): array
    {
        $attributes = [];

        foreach ($this->attributes() as $field => $label) {
            $attributes[$field] = __('Arabic :field', ['field' => $label]);
        }

        return $attributes;
    }

    /**
     * @return array<string, array{label: string, kind: string, max: int|null}>
     */
    private function textFields(PageBlockDefinition $definition): array
    {
        return array_filter($definition->editableFields(), fn (array $spec): bool => $spec['kind'] === PageBlockDefinition::FIELD_TEXT);
    }

    /**
     * Rules for the upload of an image block.
     *
     * @return list<string>
     */
    public static function uploadRules(): array
    {
        return Uploads::imageRules();
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function blockRules(string $key, PageBlockDefinition $definition): array
    {
        return match ($definition->type) {
            PageBlockType::ShortText => [$key => ['nullable', 'string', 'max:'.$definition->max]],
            PageBlockType::LongText => [$key => ['nullable', 'string', 'max:'.($definition->max * 4), new PlainTextMax((int) $definition->max)]],
            PageBlockType::Link => [
                $key => ['array'],
                $key.'.label' => ['nullable', 'string', 'max:'.$definition->max],
                $key.'.route' => ['nullable', 'string', Rule::in(PageLinks::routes())],
                $key.'.url' => ['nullable', 'string', 'url:'.implode(',', PageLinks::URL_SCHEMES), 'max:'.PageLinks::URL_MAX_LENGTH],
            ],
            PageBlockType::List => $this->listRules($key, $definition),
            PageBlockType::Image => $this->imageRules($key, true),
            PageBlockType::Seo => [
                $key => ['array'],
                $key.'.title' => ['nullable', 'string', 'max:'.PageBlockDefinition::SEO_TITLE_MAX],
                $key.'.description' => ['nullable', 'string', 'max:'.PageBlockDefinition::SEO_DESCRIPTION_MAX],
                ...$this->imageRules($key, false),
            ],
        };
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function listRules(string $key, PageBlockDefinition $definition): array
    {
        $rules = [$key => array_values(array_filter(['array', $definition->maxItems === null ? null : 'max:'.$definition->maxItems]))];

        if ($definition->fixedItems) {
            $rules[$key.'.*.key'] = ['required', 'string', Rule::in(array_column((array) $definition->default, 'key'))];
        }

        foreach ($definition->editableFields() as $field => $spec) {
            $rules[$key.'.*.'.$field] = $spec['kind'] === PageBlockDefinition::FIELD_ROUTE
                ? ['nullable', 'string', Rule::in(PageLinks::routes())]
                : ['nullable', 'string', 'max:'.$spec['max']];
        }

        return $rules;
    }

    /**
     * The image's details: alt text always, and for a photograph on the page
     * its caption, place and year too (every photo on the site carries them).
     *
     * @return array<string, list<mixed>>
     */
    private function imageRules(string $key, bool $photograph): array
    {
        $media = $key.'.media_id';
        $rules = [
            $key => ['array'],
            $media => ['nullable', 'integer', 'min:1'],
            $key.'.alt_text' => ['nullable', 'required_with:'.$media, 'string', 'max:'.PageBlockDefinition::IMAGE_TEXT_MAX],
        ];

        if (! $photograph) {
            return $rules;
        }

        return $rules + [
            $key.'.caption' => ['nullable', 'required_with:'.$media, 'string', 'max:'.PageBlockDefinition::IMAGE_TEXT_MAX],
            $key.'.place' => ['nullable', 'required_with:'.$media, 'string', 'max:'.PageBlockDefinition::IMAGE_TEXT_MAX],
            $key.'.year' => ['nullable', 'required_with:'.$media, 'integer', 'min:1900', 'max:'.(int) date('Y')],
            $key.'.focus_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            $key.'.focus_y' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
