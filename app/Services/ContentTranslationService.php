<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ContentTypes;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\Placeholders;
use App\Models\Concerns\HasTranslatableContent;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Content in more than one language, across every content type: which
 * records still need a translation, and (for the development data) Arabic
 * placeholder copy marked "[نص مؤقت]" wherever a record or its images have
 * English but no Arabic, so the Arabic site can be reviewed before the real
 * translations arrive and nothing placeholder is mistaken for approved text.
 */
class ContentTranslationService
{
    /** Placeholder wording per column (or list field), after the marker. */
    private const array WORDING = [
        'name' => 'الاسم بالعربية',
        'title' => 'العنوان بالعربية',
        'affiliation' => 'الجهة الأكاديمية',
        'organisation' => 'اسم الجهة',
        'short_bio' => 'نبذة قصيرة بالعربية تُستبدل بالنص المعتمد.',
        'full_bio' => 'السيرة الكاملة بالعربية تُستبدل بالنص المعتمد قبل الإطلاق.',
        'contact_note' => 'ملاحظة التواصل بالعربية.',
        'role_description' => 'وصف الدور بالعربية يُستبدل بالنص المعتمد.',
        'abstract' => 'ملخص العمل بالعربية يُستبدل بالملخص المعتمد.',
        'description' => 'وصف بالعربية يُستبدل بالنص المعتمد.',
        'course_description' => 'وصف المقرر بالعربية.',
        'event' => 'اسم المناسبة',
        'location' => 'المكان',
        'summary' => 'ملخص بالعربية يُستبدل بالنص المعتمد.',
        'epigraph' => 'استهلال بالعربية.',
        'body' => 'نص المقالة بالعربية، يُستبدل بالنص المعتمد قبل الإطلاق.',
        'quote' => 'اقتباس بالعربية يُستبدل بالنص المعتمد.',
        'attribution' => 'صاحب القول',
        'label' => 'التسمية',
        'value' => 'القيمة',
        MediaProperties::ALT_TEXT => 'وصف الصورة بالعربية',
        MediaProperties::CAPTION => 'تعليق الصورة بالعربية',
        MediaProperties::PLACE => 'المكان',
        MediaProperties::NOTE => 'ملاحظة بالعربية',
    ];

    private const string ITEM = 'بند';

    public function __construct(private readonly MediaRepositoryInterface $media) {}

    /**
     * Records of every content type with a column filled in English but not in the language.
     */
    public function countNeedingTranslation(string $locale = Locales::AR): int
    {
        $count = 0;

        foreach (ContentTypes::services() as $service) {
            $count += app($service)->countNeedingTranslation($locale);
        }

        return $count;
    }

    /**
     * Fill every empty Arabic column (and every image's empty Arabic
     * description) with marked placeholder copy. Returns how many records
     * changed. Text already written in Arabic is never touched.
     */
    public function seedArabicPlaceholders(): int
    {
        $changed = 0;

        foreach (array_keys(ContentTypes::services()) as $type) {
            foreach ($this->repository($type)->all() as $record) {
                if (! in_array(HasTranslatableContent::class, class_uses_recursive($record), true)) {
                    continue;
                }

                if ($this->fill($record)) {
                    $changed++;
                }

                if ($record instanceof HasMedia) {
                    $this->fillMedia($record);
                }
            }
        }

        return $changed;
    }

    /**
     * The placeholder for one column's English value, in the same shape
     * (strings, lists and list items keep their structure).
     */
    public function placeholder(string $column, mixed $english): mixed
    {
        if (is_array($english)) {
            $placeholder = [];

            foreach ($english as $key => $item) {
                $placeholder[$key] = $this->placeholder(is_string($key) ? $key : self::ITEM, $item);
            }

            return $placeholder;
        }

        if (! is_string($english) || trim($english) === '') {
            return $english;
        }

        $text = Placeholders::MARKER_AR.' '.(self::WORDING[$column] ?? self::ITEM);

        return str_contains($english, '<') ? '<p>'.$text.'</p>' : $text;
    }

    /**
     * @param  Model&HasTranslatableContent  $record
     */
    private function fill(Model $record): bool
    {
        $dirty = false;

        foreach ($record->missingTranslations(Locales::AR) as $column) {
            $english = $record->getTranslation($column, Locales::DEFAULT, false);
            $record->setTranslation($column, Locales::AR, $this->placeholder($column, $english));
            $dirty = true;
        }

        if ($dirty) {
            // Saved with its events, so the search column takes in the Arabic too.
            $record->save();
        }

        return $dirty;
    }

    private function fillMedia(HasMedia $record): void
    {
        foreach ($record->media as $media) {
            $properties = [];

            foreach (MediaProperties::translatable() as $property) {
                $english = data_get($media->custom_properties, $property);
                $path = MediaProperties::translationPath(Locales::AR, $property);

                if (is_string($english) && trim($english) !== '' && blank(data_get($media->custom_properties, $path))) {
                    $properties[$path] = $this->placeholder($property, $english);
                }
            }

            if ($properties !== []) {
                $this->media->updateProperties($media, $properties);
            }
        }
    }

    private function repository(string $type): BaseRepositoryInterface
    {
        $model = class_basename(ContentTypes::get($type)['model']);

        return app('App\\Repositories\\Contracts\\'.$model.'RepositoryInterface');
    }
}
