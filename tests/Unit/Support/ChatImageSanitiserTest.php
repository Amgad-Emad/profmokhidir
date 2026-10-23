<?php

namespace Tests\Unit\Support;

use App\Support\ChatImageSanitiser;
use App\Support\ChatSampleFiles;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatImageSanitiserTest extends TestCase
{
    #[Test]
    public function it_strips_the_gps_location_and_keeps_a_rotated_photo_upright(): void
    {
        $path = ChatSampleFiles::jpegWithLocation(120, 80, orientation: 6);
        $this->assertSame('N', exif_read_data($path)['GPSLatitudeRef'] ?? null);

        (new ChatImageSanitiser)->clean($path, 'jpg');

        $exif = @exif_read_data($path) ?: [];
        $this->assertArrayNotHasKey('GPSLatitudeRef', $exif);
        $this->assertArrayNotHasKey('GPSLatitude', $exif);
        $this->assertArrayNotHasKey('Orientation', $exif);
        // Orientation 6 (rotate 90° clockwise) is applied to the pixels: 120 × 80 becomes 80 × 120.
        [$width, $height] = getimagesize($path);
        $this->assertSame([80, 120], [$width, $height]);
    }

    #[Test]
    public function png_and_webp_are_re_encoded_in_their_own_format(): void
    {
        $png = ChatSampleFiles::png(30, 20);
        (new ChatImageSanitiser)->clean($png, 'png');
        $this->assertSame('image/png', getimagesize($png)['mime']);

        $webp = sys_get_temp_dir().'/'.uniqid('chat', true).'.webp';
        imagewebp(imagecreatetruecolor(10, 10), $webp);
        (new ChatImageSanitiser)->clean($webp, 'webp');
        $this->assertSame('image/webp', getimagesize($webp)['mime']);
    }

    #[Test]
    public function a_file_that_is_not_an_image_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);

        (new ChatImageSanitiser)->clean(ChatSampleFiles::pdf(), 'jpg');
    }
}
