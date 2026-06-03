<?php

namespace ExpressionEngine\Tests\Addons\Colorpicker;

require_once SYSPATH . 'ee/legacy/fieldtypes/EE_Fieldtype.php';
require_once SYSPATH . 'ee/Mexitek/PHPColors/Color.php';
require_once SYSPATH . 'ee/ExpressionEngine/Addons/colorpicker/ft.colorpicker.php';

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
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $messages);
    }
}
