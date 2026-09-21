<?php

namespace ExpressionEngine\Tests\Service\Accessibility\Color;

use ExpressionEngine\Service\Accessibility\Color\ContrastAlgorithm;
use ExpressionEngine\Service\Accessibility\Color\Gpc;
use PHPUnit\Framework\TestCase;

class GpcTest extends TestCase
{
    private $gpc;

    public function setUp(): void
    {
        $this->gpc = new Gpc();
    }

    public function testImplementsContrastAlgorithmInterface()
    {
        $this->assertInstanceOf(ContrastAlgorithm::class, $this->gpc);
        $this->assertSame(60.0, $this->gpc->defaultTarget());
    }

    public function testForegroundForBackgroundReturnsDefaultTargetColor()
    {
        $this->assertSame('#8e8e8e', $this->gpc->foregroundForBackground(['R' => 255, 'G' => 255, 'B' => 255]));
        $this->assertSame('#b1b1b1', $this->gpc->foregroundForBackground(['R' => 0, 'G' => 0, 'B' => 0]));
        $this->assertSame('#d8d8d8', $this->gpc->foregroundForBackground(['R' => 51, 'G' => 102, 'B' => 153]));
    }

    public function testForegroundForBackgroundAcceptsTarget()
    {
        $this->assertSame('#6e6e6e', $this->gpc->foregroundForBackground(['R' => 255, 'G' => 255, 'B' => 255], 75));
        $this->assertSame('#ffffff', $this->gpc->foregroundForBackground(['R' => 136, 'G' => 136, 'B' => 136], 75));
    }

    public function testAdjustForegroundForBackgroundPreservesForegroundHue()
    {
        $background = ['R' => 51, 'G' => 102, 'B' => 153];

        $this->assertSame(
            '#ead5bf',
            $this->gpc->adjustForegroundForBackground(['R' => 153, 'G' => 102, 'B' => 51], $background, 60)
        );

        $this->assertSame(
            '#c8dbed',
            $this->gpc->adjustForegroundForBackground(['R' => 38, 'G' => 77, 'B' => 115], $background, 60)
        );
    }

    public function testAdjustedForegroundMeetsRequestedTarget()
    {
        $background = ['R' => 51, 'G' => 102, 'B' => 153];
        $foreground = $this->hexToRgb(
            $this->gpc->adjustForegroundForBackground(['R' => 153, 'G' => 102, 'B' => 51], $background, 60)
        );

        $this->assertGreaterThanOrEqual(60, abs($this->gpc->contrast($foreground, $background)));
    }

    public function testZeroTargetReturnsBackgroundColor()
    {
        $this->assertSame('#336699', $this->gpc->foregroundForBackground(['R' => 51, 'G' => 102, 'B' => 153], 0));
    }

    public function testNormalizeTargetClampsToSupportedRange()
    {
        $this->assertSame(60.0, $this->gpc->normalizeTarget(60));
        $this->assertSame(108.0, $this->gpc->normalizeTarget(120));
        $this->assertSame(-108.0, $this->gpc->normalizeTarget(-120));
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            'R' => hexdec(substr($hex, 0, 2)),
            'G' => hexdec(substr($hex, 2, 2)),
            'B' => hexdec(substr($hex, 4, 2)),
        ];
    }
}
