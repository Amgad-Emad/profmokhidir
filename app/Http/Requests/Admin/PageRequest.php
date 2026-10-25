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
            $label = mb_strtolower($definition->label);

            $attributes[$key] = $label;
            $attributes[$key.'.label'] = $label.' label';
            $attributes[$key.'.url'] = $label.' web address';
            $attributes[$key.'.route'] = $label.' destination';
            $attributes[$key.'.alt_text'] = 'alt text';
            $attributes[$key.'.caption'] = 'caption';
            $attributes[$key.'.place'] = 'place';
            $attributes[$key.'.year'] = 'year';
            $attributes[$key.'.title'] = 'search title';
            $attributes[$key.'.description'] = 'search description';

            foreach ($definition->editableFields() as $field => $spec) {
                $attributes[$key.'.*.'.$field] = mb_strtolower($definition->itemLabel.' '.$spec['label']);
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
            '*.alt_text.required_with' => 'Describe the image for people who cannot see it.',
            '*.caption.required_with' => 'Every photograph needs a caption.',
            '*.place.required_with' => 'Every photograph needs the place it was taken.',
            '*.year.required_with' => 'Every photograph needs the year it was taken.',
            '*.url.url' => 'Enter a full web address beginning with https://.',
        ];
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
