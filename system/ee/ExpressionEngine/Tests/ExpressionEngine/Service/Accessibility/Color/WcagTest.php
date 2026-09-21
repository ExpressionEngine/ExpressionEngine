<?php

namespace ExpressionEngine\Tests\Service\Accessibility\Color;

use ExpressionEngine\Service\Accessibility\Color\ContrastAlgorithm;
use ExpressionEngine\Service\Accessibility\Color\Wcag;
use PHPUnit\Framework\TestCase;

class WcagTest extends TestCase
{
    private $wcag;

    public function setUp(): void
    {
        $this->wcag = new Wcag();
    }

    public function testImplementsContrastAlgorithmInterface()
    {
        $this->assertInstanceOf(ContrastAlgorithm::class, $this->wcag);
        $this->assertSame(4.5, $this->wcag->defaultTarget());
    }

    public function testContrastRatioMatchesKnownWcagEndpoints()
    {
        $this->assertSame(21.0, $this->wcag->contrastRatio(
            ['R' => 0, 'G' => 0, 'B' => 0],
            ['R' => 255, 'G' => 255, 'B' => 255]
        ));

        $this->assertSame(1.0, $this->wcag->contrastRatio(
            ['R' => 51, 'G' => 102, 'B' => 153],
            ['R' => 51, 'G' => 102, 'B' => 153]
        ));

        $this->assertSame(21.0, $this->wcag->contrast(
            ['R' => 0, 'G' => 0, 'B' => 0],
            ['R' => 255, 'G' => 255, 'B' => 255]
        ));
    }

    public function testForegroundForBackgroundReturnsDefaultRatioColor()
    {
        $this->assertSame('#767676', $this->wcag->foregroundForBackground(['R' => 255, 'G' => 255, 'B' => 255]));
        $this->assertSame('#757575', $this->wcag->foregroundForBackground(['R' => 0, 'G' => 0, 'B' => 0]));
        $this->assertSame('#dfdfdf', $this->wcag->foregroundForBackground(['R' => 51, 'G' => 102, 'B' => 153]));
    }

    public function testForegroundForBackgroundAcceptsRatio()
    {
        $this->assertSame('#595959', $this->wcag->foregroundForBackground(['R' => 255, 'G' => 255, 'B' => 255], 7));
        $this->assertSame('#212121', $this->wcag->foregroundForBackground(['R' => 136, 'G' => 136, 'B' => 136], 4.5));
    }

    public function testAdjustForegroundForBackgroundPreservesForegroundHue()
    {
        $background = ['R' => 51, 'G' => 102, 'B' => 153];

        $this->assertSame(
            '#eeddcb',
            $this->wcag->adjustForegroundForBackground(['R' => 153, 'G' => 102, 'B' => 51], $background, 4.5)
        );

        $this->assertSame(
            '#d2e1f0',
            $this->wcag->adjustForegroundForBackground(['R' => 38, 'G' => 77, 'B' => 115], $background, 4.5)
        );
    }

    public function testAdjustedForegroundMeetsRequestedRatio()
    {
        $background = ['R' => 51, 'G' => 102, 'B' => 153];
        $foreground = $this->hexToRgb(
            $this->wcag->adjustForegroundForBackground(['R' => 153, 'G' => 102, 'B' => 51], $background, 4.5)
        );

        $this->assertGreaterThanOrEqual(4.5, $this->wcag->contrastRatio($foreground, $background));
    }

    public function testMinimumRatioReturnsBackgroundColor()
    {
        $this->assertSame('#336699', $this->wcag->foregroundForBackground(['R' => 51, 'G' => 102, 'B' => 153], 1));
    }

    public function testNormalizeRatioClampsToWcagRange()
    {
        $this->assertSame(4.5, $this->wcag->normalizeRatio(4.5));
        $this->assertSame(1.0, $this->wcag->normalizeRatio(0));
        $this->assertSame(21.0, $this->wcag->normalizeRatio(40));
        $this->assertSame(21.0, $this->wcag->normalizeTarget(40));
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
