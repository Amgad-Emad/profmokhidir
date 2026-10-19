<?php

namespace Database\Seeders;

use App\Constants\VideoProvider;
use App\Models\Video;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Video::query()->exists()) {
            return;
        }

        $videos = [
            ['Inaugural lecture: The soil beneath our feet', '1983-03-12', 'Inaugural Lecture', 'Khartoum', VideoProvider::YouTube, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['Interview on agricultural education', '2006-11-02', 'Sudan TV archive', 'Khartoum', VideoProvider::Vimeo, 'https://vimeo.com/76979871'],
            ['Keynote: Fifty years of the Gezira', '2008-09-20', 'FAO Regional Workshop', 'Wad Madani', VideoProvider::Link, 'https://example.com/placeholder-keynote'],
            ['Students remember Prof. Khidir', '2019-05-15', 'Alumni Gathering', 'Khartoum', VideoProvider::YouTube, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ];

        foreach ($videos as $index => [$title, $date, $event, $location, $provider, $url]) {
            Video::factory()->published()->withThumbnail()->create([
                'title' => '[Placeholder] '.$title,
                'date' => $date,
                'event' => '[Placeholder] '.$event,
                'location' => $location,
                'description' => '[Placeholder] Description of this recording, to be replaced with approved text.',
                'provider' => $provider,
                'media_url' => $url,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
