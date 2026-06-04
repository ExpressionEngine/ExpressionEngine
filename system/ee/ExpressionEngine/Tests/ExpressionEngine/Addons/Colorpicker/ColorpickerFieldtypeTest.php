<?php

namespace ExpressionEngine\Tests\Addons\Colorpicker;

require_once SYSPATH . 'ee/legacy/fieldtypes/EE_Fieldtype.php';
require_once SYSPATH . 'ee/Mexitek/PHPColors/Color.php';
require_once SYSPATH . 'ee/ExpressionEngine/Addons/colorpicker/ft.colorpicker.php';

use ExpressionEngine\Service\Accessibility\Color\Gpc;
use ExpressionEngine\Service\Accessibility\Color\Wcag;
use Mexitek\PHPColors\Color;
use PHPUnit\Framework\TestCase;

class ColorpickerFieldtypeTest extends TestCase
{
    private $fieldtype;

    public function setUp(): void
    {
        $reflection = new \ReflectionClass(\Colorpicker_ft::class);
        $this->fieldtype = $reflection->newInstanceWithoutConstructor();
    }

    public function testRotatePreservesFractionalHue()
    {
        $this->assertSame(
            '#335599',
            $this->fieldtype->replace_rotate('#336699', ['degrees' => '10'])
        );
    }

    public function testRotateWrapsNegativeDegrees()
    {
        $this->assertSame(
            '#993344',
            $this->fieldtype->replace_rotate('#336699', ['degrees' => '-220'])
        );
    }

    public function testContrastRatioConstrainsColorModifiers()
    {
        $color = '#336699';
        $params = ['contrast_ratio' => '4.5'];
        $results = [
            [$this->fieldtype->replace_complementary($color, $params), '#eeddcb'],
            [$this->fieldtype->replace_darken($color, ['percent' => '10'] + $params), '#d2e1f0'],
            [$this->fieldtype->replace_lighten($color, ['percent' => '10'] + $params), '#d2e1f0'],
            [$this->fieldtype->replace_rotate($color, ['degrees' => '170'] + $params), '#f0dbd1'],
            [$this->fieldtype->replace_saturate($color, ['percent' => '20'] + $params), '#cde2f6'],
            [$this->fieldtype->replace_desaturate($color, ['percent' => '20'] + $params), '#d7e1ea'],
            [$this->fieldtype->replace_mix($color, ['color' => '#ffffff', 'percent' => '50'] + $params), '#d7e1eb'],
        ];

        foreach ($results as [$actual, $expected]) {
            $this->assertSame($expected, $actual);
            $this->assertContrastRatioAtLeast($actual, $color, 4.5);
        }
    }

    public function testContrastRatioDefaultsToFourPointFiveWhenEmpty()
    {
        $this->assertSame(
            $this->fieldtype->replace_complementary('#336699', ['contrast_ratio' => '4.5']),
            $this->fieldtype->replace_complementary('#336699', ['contrast_ratio' => ''])
        );
    }

    public function testContrastRatioTakesPrecedenceOverPerceptualContrast()
    {
        $result = $this->fieldtype->replace_complementary('#336699', [
            'contrast_ratio' => '4.5',
            'perceptual_contrast' => '60',
        ]);

        $this->assertSame('#eeddcb', $result);
        $this->assertContrastRatioAtLeast($result, '#336699', 4.5);
    }

    public function testPerceptualContrastConstrainsColorModifiers()
    {
        $color = '#336699';
        $params = ['perceptual_contrast' => '60'];
        $results = [
            [$this->fieldtype->replace_complementary($color, $params), '#ead5bf'],
            [$this->fieldtype->replace_darken($color, ['percent' => '10'] + $params), '#c8dbed'],
            [$this->fieldtype->replace_lighten($color, ['percent' => '10'] + $params), '#c8dbed'],
            [$this->fieldtype->replace_rotate($color, ['degrees' => '170'] + $params), '#ecd4c7'],
            [$this->fieldtype->replace_saturate($color, ['percent' => '20'] + $params), '#c2dbf4'],
            [$this->fieldtype->replace_desaturate($color, ['percent' => '20'] + $params), '#cedae5'],
            [$this->fieldtype->replace_mix($color, ['color' => '#ffffff', 'percent' => '50'] + $params), '#cddae6'],
        ];

        foreach ($results as [$actual, $expected]) {
            $this->assertSame($expected, $actual);
            $this->assertPerceptualContrastAtLeast($actual, $color, 60);
        }
    }

    public function testPerceptualContrastDefaultsToSixtyWhenEmpty()
    {
        $this->assertSame(
            $this->fieldtype->replace_complementary('#336699', ['perceptual_contrast' => '60']),
            $this->fieldtype->replace_complementary('#336699', ['perceptual_contrast' => ''])
        );
    }

    public function testZeroPercentDarkenAndLightenReturnOriginalColor()
    {
        $this->assertSame('#336699', $this->fieldtype->replace_darken('#336699', ['percent' => '0']));
        $this->assertSame('#336699', $this->fieldtype->replace_lighten('#336699', ['percent' => '0']));
    }

    public function testMixPercentRepresentsAmountOfSecondColor()
    {
        $this->assertSame('#99b2cc', $this->fieldtype->replace_mix('#336699', ['color' => '#ffffff']));
        $this->assertSame('#336699', $this->fieldtype->replace_mix('#336699', ['color' => '#ffffff', 'percent' => '0']));
        $this->assertSame('#99b2cc', $this->fieldtype->replace_mix('#336699', ['color' => '#ffffff', 'percent' => '50']));
        $this->assertSame('#ffffff', $this->fieldtype->replace_mix('#336699', ['color' => '#ffffff', 'percent' => '100']));
    }

    public function testHslReturnsRoundedCssComponents()
    {
        $this->assertSame('210, 50%, 40%', $this->fieldtype->replace_hsl('#336699'));
        $this->assertSame('0, 0%, 100%', $this->fieldtype->replace_hsl('#ffffff'));
    }

    public function testDecimalPercentModifiersDoNotTriggerPrecisionDeprecations()
    {
        $messages = [];

        set_error_handler(function ($severity, $message) use (&$messages) {
            $messages[] = $message;

            return true;
        });

        try {
            $this->assertSame('#264d73', $this->fieldtype->replace_darken('#336699', ['percent' => '10.5']));
            $this->assertSame('#4080bf', $this->fieldtype->replace_lighten('#336699', ['percent' => '10.5']));
            $this->assertSame('#2866a4', $this->fieldtype->replace_saturate('#336699', ['percent' => '10.5']));
            $this->assertSame('#3e668e', $this->fieldtype->replace_desaturate('#336699', ['percent' => '10.5']));
            $this->assertSame('#4876a3', $this->fieldtype->replace_mix('#336699', ['color' => '#ffffff', 'percent' => '10.5']));
            $this->assertSame('#d6e1eb', $this->fieldtype->replace_mix('#336699', [
                'color' => '#ffffff',
                'percent' => '50.5',
                'contrast_ratio' => '4.5',
            ]));
            $this->assertSame('#cddae6', $this->fieldtype->replace_mix('#336699', [
                'color' => '#ffffff',
                'percent' => '50.5',
                'perceptual_contrast' => '60',
            ]));
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $messages);
    }

    private function assertContrastRatioAtLeast(string $foreground, string $background, float $minimum): void
    {
        $wcag = new Wcag();

        $this->assertGreaterThanOrEqual(
            $minimum,
            $wcag->contrastRatio(Color::hexToRgb($foreground), Color::hexToRgb($background))
        );
    }

    private function assertPerceptualContrastAtLeast(string $foreground, string $background, float $minimum): void
    {
        $gpc = new Gpc();

        $this->assertGreaterThanOrEqual(
            $minimum,
            abs($gpc->contrast(Color::hexToRgb($foreground), Color::hexToRgb($background)))
        );
    }
}
