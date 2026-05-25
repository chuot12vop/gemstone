<?php

namespace Tests\Unit;

use App\Services\ImageWebpEncoder;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageWebpEncoderTest extends TestCase
{
    public function test_encodes_jpeg_to_webp_when_gd_supports_webp(): void
    {
        $encoder = new ImageWebpEncoder();
        if (! $encoder->canEncode()) {
            $this->markTestSkipped('GD WebP support is not available.');
        }

        $source = imagecreatetruecolor(4, 4);
        $jpegPath = sys_get_temp_dir().'/gemstone-test-'.uniqid('', true).'.jpg';
        imagejpeg($source, $jpegPath, 90);
        imagedestroy($source);

        $webpPath = sys_get_temp_dir().'/gemstone-test-'.uniqid('', true).'.webp';
        $file = new UploadedFile($jpegPath, 'test.jpg', 'image/jpeg', null, true);

        $this->assertTrue($encoder->encodeUploadedFile($file, $webpPath));
        $this->assertFileExists($webpPath);
        $this->assertSame('image/webp', mime_content_type($webpPath));

        @unlink($jpegPath);
        @unlink($webpPath);
    }
}
