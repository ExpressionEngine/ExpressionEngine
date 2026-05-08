<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/Image_lib.php';

use PHPUnit\Framework\TestCase;

class ImageLibAlphaPreservationTest extends TestCase
{
    /**
     * Reset singleton mocks before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    /**
     * Reset singleton mocks after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Ensure WEBP images initialize a transparent destination canvas.
     *
     * @return void
     */
    public function testImagePreserveAlphaPreservesWebpTransparency(): void
    {
        $this->skipWhenGdImageFunctionsAreUnavailable();

        $sourceImage = $this->createTransparentSourceImage();
        $destinationImage = imagecreatetruecolor(4, 4);

        $imageLib = new \EE_Image_lib();
        $imageLib->image_type = 18;
        $imageLib->image_preserve_alpha($destinationImage, $sourceImage);

        imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, 4, 4, 4, 4);

        $this->assertSame(127, $this->getAlphaValueForPixel($destinationImage, 0, 0));

        imagedestroy($sourceImage);
        imagedestroy($destinationImage);
    }

    /**
     * Ensure AVIF images initialize a transparent destination canvas.
     *
     * @return void
     */
    public function testImagePreserveAlphaPreservesAvifTransparency(): void
    {
        $this->skipWhenGdImageFunctionsAreUnavailable();

        $sourceImage = $this->createTransparentSourceImage();
        $destinationImage = imagecreatetruecolor(4, 4);

        $imageLib = new \EE_Image_lib();
        $imageLib->image_type = 19;
        $imageLib->image_preserve_alpha($destinationImage, $sourceImage);

        imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, 4, 4, 4, 4);

        $this->assertSame(127, $this->getAlphaValueForPixel($destinationImage, 0, 0));

        imagedestroy($sourceImage);
        imagedestroy($destinationImage);
    }

    /**
     * Build an in-memory fully transparent source image.
     *
     * @return resource
     */
    private function createTransparentSourceImage()
    {
        $sourceImage = imagecreatetruecolor(4, 4);
        imagealphablending($sourceImage, false);
        imagesavealpha($sourceImage, true);

        $transparentColor = imagecolorallocatealpha($sourceImage, 0, 0, 0, 127);
        imagefill($sourceImage, 0, 0, $transparentColor);

        return $sourceImage;
    }

    /**
     * Read alpha value for a single pixel.
     *
     * @param resource $image Image resource to inspect.
     * @param int $x Horizontal pixel coordinate.
     * @param int $y Vertical pixel coordinate.
     * @return int
     */
    private function getAlphaValueForPixel($image, int $x, int $y): int
    {
        $pixel = imagecolorat($image, $x, $y);
        $channels = imagecolorsforindex($image, $pixel);

        return $channels['alpha'];
    }

    /**
     * Skip the test class when required GD functions are unavailable.
     *
     * @return void
     */
    private function skipWhenGdImageFunctionsAreUnavailable(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is required for alpha-preservation tests.');
        }

        if (! function_exists('imagecopyresampled')) {
            $this->markTestSkipped('GD resampling functions are required for alpha-preservation tests.');
        }

        if (! function_exists('imagecolorallocatealpha')) {
            $this->markTestSkipped('GD alpha color allocation is required for alpha-preservation tests.');
        }
    }
}
