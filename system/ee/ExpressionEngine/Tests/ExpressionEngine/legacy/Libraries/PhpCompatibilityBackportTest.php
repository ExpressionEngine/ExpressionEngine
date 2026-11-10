<?php

namespace ExpressionEngine\Tests\legacy\Libraries;

use ExpressionEngine\Library\Captcha;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PhpCompatibilityBackportTest extends TestCase
{
    private $directory;
    private $reporting;

    protected function setUp(): void
    {
        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once BASEPATH . 'helpers/captcha_helper.php';
        require_once BASEPATH . 'libraries/Image_lib.php';
        ee()->resetMocks();
        $this->reporting = error_reporting(E_ALL);
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            return false;
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        error_reporting($this->reporting);
        ee()->resetMocks();
        if ($this->directory) {
            foreach (glob($this->directory . '*') as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
    }

    /** @dataProvider fontModes */
    public function testLegacyCaptchaCreatesJpegWithFractionalGeometry($font)
    {
        $this->requireImageSupport($font);
        $captcha = create_captcha([
            'word' => 'example', 'img_path' => $this->temporaryDirectory(), 'img_url' => '/captcha/',
            'img_width' => 153, 'img_height' => 31,
            'font_path' => $font ? BASEPATH . 'fonts/texb.ttf' : '',
        ]);
        $this->assertSame('example', $captcha['word']);
        $this->assertJpeg($captcha['image'], 153, 31);
    }

    /** @dataProvider fontModes */
    public function testCaptchaLibraryCreatesJpegWithoutWarnings($font)
    {
        $this->requireImageSupport($font);
        $directory = $this->temporaryDirectory();
        ee()->setMock('config', new class($directory, $font) {
            private $directory;
            private $font;
            public function __construct($directory, $font) { $this->directory = $directory; $this->font = $font; }
            public function item($key) { return $key === 'captcha_font' ? ($this->font ? 'y' : 'n') : ($key === 'captcha_require_members' ? 'y' : 'n'); }
            public function slash_item($key, $unused = null) { return $key === 'captcha_path' ? $this->directory : '/captcha/'; }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
        });
        // Occasional cleanup is independent of rendering the supplied word.
        ee()->setMock('Model', new class {
            public function get($name) { return $this; }
            public function filter($name, $operator, $value) { return $this; }
            public function delete() {}
        });
        $captcha = new Captcha();
        $this->assertJpeg($captcha->create('example', true), 140, 30);
        $this->assertSame('example', $captcha->cached_captcha);
    }

    public function fontModes()
    {
        return ['bitmap' => [false], 'truetype' => [true]];
    }

    public function testImageResizePreservesTransparency()
    {
        if (!function_exists('imagepng')) {
            $this->markTestSkipped('GD PNG support is required.');
        }
        $folder = $this->temporaryDirectory();
        $source = imagecreatetruecolor(4, 4);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        imagefill($source, 0, 0, imagecolorallocatealpha($source, 0, 0, 0, 127));
        imagepng($source, $folder . 'source.png');
        if (PHP_VERSION_ID < 80000) { imagedestroy($source); }
        $library = new \EE_Image_lib([
            'source_image' => $folder . 'source.png', 'new_image' => $folder . 'resized.png',
            'width' => 2, 'height' => 2,
        ]);
        $this->assertTrue($library->resize());
        $image = imagecreatefrompng($folder . 'resized.png');
        $this->assertSame([2, 2], [imagesx($image), imagesy($image)]);
        $this->assertSame(127, imagecolorsforindex($image, imagecolorat($image, 0, 0))['alpha']);
        if (PHP_VERSION_ID < 80000) { imagedestroy($image); }
    }

    private function temporaryDirectory()
    {
        if (!$this->directory) {
            $this->directory = sys_get_temp_dir() . '/ee-captcha-compatibility-' . uniqid() . '/';
            mkdir($this->directory);
        }
        return $this->directory;
    }

    private function requireImageSupport($font)
    {
        if (!function_exists('imagejpeg') || ($font && !function_exists('imagettftext'))) {
            $this->markTestSkipped('GD JPEG/TrueType support is required.');
        }
        if ($font) { $this->assertFileExists(BASEPATH . 'fonts/texb.ttf'); }
    }

    private function assertJpeg($html, $width, $height)
    {
        $this->assertSame(1, preg_match('~src="/captcha/([^"/]+)"~', $html, $match));
        $path = $this->directory . $match[1];
        $this->assertFileExists($path);
        $size = getimagesize($path);
        $this->assertSame([$width, $height, IMAGETYPE_JPEG], array_slice($size, 0, 3));
        $image = imagecreatefromjpeg($path);
        $this->assertNotFalse($image);
        if (PHP_VERSION_ID < 80000) { imagedestroy($image); }
    }
}
