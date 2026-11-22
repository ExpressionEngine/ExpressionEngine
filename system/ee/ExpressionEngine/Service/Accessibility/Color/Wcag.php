<?php

namespace ExpressionEngine\Service\Accessibility\Color;

/**
 * Calculate and search WCAG 2.x contrast-ratio values for sRGB colors.
 *
 * This service implements the relative luminance and contrast-ratio formulas
 * from WCAG 2.x for web content in the sRGB color space.
 *
 * Reference material:
 * - https://www.w3.org/TR/WCAG22/#dfn-relative-luminance
 * - https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
 * - https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html
 */
class Wcag implements ContrastAlgorithm
{
    /**
     * Return the default WCAG contrast-ratio target.
     *
     * @return float
     */
    public function defaultTarget(): float
    {
        return 4.5;
    }

    /**
     * Clamp a requested WCAG contrast-ratio target.
     *
     * @param float $value
     * @return float
     */
    public function normalizeTarget(float $value): float
    {
        return $this->normalizeRatio($value);
    }

    /**
     * Calculate the WCAG contrast ratio between two colors.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @return float A ratio from 1.0 to 21.0.
     */
    public function contrast(array $foregroundRgb, array $backgroundRgb): float
    {
        return $this->contrastRatio($foregroundRgb, $backgroundRgb);
    }

    /**
     * Calculate the WCAG contrast ratio between two colors.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @return float A ratio from 1.0 to 21.0.
     */
    public function contrastRatio(array $foregroundRgb, array $backgroundRgb): float
    {
        return $this->contrastRatioFromLuminance(
            $this->relativeLuminance($foregroundRgb),
            $this->relativeLuminance($backgroundRgb)
        );
    }

    /**
     * Find a grayscale foreground color that reaches the requested ratio.
     *
     * When the exact target cannot be reached, the method returns whichever
     * grayscale endpoint has the highest available contrast.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @param float $targetRatio
     * @return string A normalized six-digit hex color including "#".
     */
    public function foregroundForBackground(array $backgroundRgb, float $targetRatio = 4.5): string
    {
        $targetRatio = $this->normalizeRatio($targetRatio);

        if ($targetRatio == 1.0) {
            return $this->rgbToHex($backgroundRgb);
        }

        return $this->findForeground($this->relativeLuminance($backgroundRgb), $targetRatio);
    }

    /**
     * Clamp a requested WCAG contrast ratio to the valid range.
     *
     * @param float $value
     * @return float
     */
    public function normalizeRatio(float $value): float
    {
        return max(1.0, min(21.0, $value));
    }

    /**
     * Adjust a foreground color until it reaches the requested contrast ratio.
     *
     * The method first returns the foreground unchanged when it already satisfies
     * the target. Otherwise it searches HSL lightness while preserving the
     * foreground hue and saturation, tries the opposite lightness direction if
     * needed, and finally falls back to a grayscale search.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @param float $targetRatio
     * @return string A normalized six-digit hex color including "#".
     */
    public function adjustForegroundForBackground(array $foregroundRgb, array $backgroundRgb, float $targetRatio = 4.5): string
    {
        $targetRatio = $this->normalizeRatio($targetRatio);
        $backgroundLuminance = $this->relativeLuminance($backgroundRgb);
        $foregroundLuminance = $this->relativeLuminance($foregroundRgb);

        if ($this->contrastRatioFromLuminance($foregroundLuminance, $backgroundLuminance) >= $targetRatio) {
            return $this->rgbToHex($foregroundRgb);
        }

        $preferredPolarity = $this->preferredPolarity($foregroundLuminance, $backgroundLuminance);
        $adjusted = $this->adjustLightness($foregroundRgb, $backgroundLuminance, $targetRatio, $preferredPolarity);

        if ($adjusted !== false) {
            return $adjusted;
        }

        $oppositePolarity = ($preferredPolarity == 'light') ? 'dark' : 'light';
        $adjusted = $this->adjustLightness($foregroundRgb, $backgroundLuminance, $targetRatio, $oppositePolarity);

        if ($adjusted !== false) {
            return $adjusted;
        }

        return $this->findForeground($backgroundLuminance, $targetRatio);
    }

    /**
     * Search for a grayscale foreground against a known background luminance.
     *
     * @param float $backgroundLuminance
     * @param float $targetRatio
     * @return string
     */
    private function findForeground(float $backgroundLuminance, float $targetRatio): string
    {
        $blackContrast = $this->contrastRatioFromLuminance($this->grayToLuminance(0), $backgroundLuminance);
        $whiteContrast = $this->contrastRatioFromLuminance($this->grayToLuminance(255), $backgroundLuminance);

        if ($blackContrast >= $targetRatio && $whiteContrast >= $targetRatio) {
            $polarity = ($backgroundLuminance >= $this->grayToLuminance(128)) ? 'dark' : 'light';

            return $this->findGray($backgroundLuminance, $targetRatio, $polarity);
        }

        if ($blackContrast >= $targetRatio) {
            return $this->findGray($backgroundLuminance, $targetRatio, 'dark');
        }

        if ($whiteContrast >= $targetRatio) {
            return $this->findGray($backgroundLuminance, $targetRatio, 'light');
        }

        return ($blackContrast >= $whiteContrast) ? '#000000' : '#ffffff';
    }

    /**
     * Choose the first lightness direction to search for a color adjustment.
     *
     * Existing non-zero contrast keeps its current direction. Equal luminance
     * falls back to the practical direction for the background lightness.
     *
     * @param float $foregroundLuminance
     * @param float $backgroundLuminance
     * @return string "dark" or "light".
     */
    private function preferredPolarity(float $foregroundLuminance, float $backgroundLuminance): string
    {
        if ($foregroundLuminance < $backgroundLuminance) {
            return 'dark';
        }

        if ($foregroundLuminance > $backgroundLuminance) {
            return 'light';
        }

        return ($backgroundLuminance >= $this->grayToLuminance(128)) ? 'dark' : 'light';
    }

    /**
     * Search HSL lightness for the smallest change that reaches the target.
     *
     * Hue and saturation are held constant so modifier-generated colors keep
     * their intended color family whenever the contrast target can be met that
     * way.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $rgb
     * @param float $backgroundLuminance
     * @param float $targetRatio
     * @param string $polarity "dark" or "light".
     * @return string|false
     */
    private function adjustLightness(array $rgb, float $backgroundLuminance, float $targetRatio, string $polarity)
    {
        $hsl = $this->rgbToHsl($rgb);
        $low = 0.0;
        $high = 1.0;
        $best = null;

        for ($i = 0; $i < 16; $i++) {
            $mid = ($low + $high) / 2;
            $candidateL = ($polarity == 'dark')
                ? $hsl['L'] * (1 - $mid)
                : $hsl['L'] + ((1 - $hsl['L']) * $mid);
            $candidateRgb = $this->hslToRgb($hsl['H'], $hsl['S'], $candidateL);
            $contrast = $this->contrastRatioFromLuminance(
                $this->relativeLuminance($candidateRgb),
                $backgroundLuminance
            );

            if ($contrast >= $targetRatio) {
                $best = $candidateRgb;
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        if ($best === null) {
            return false;
        }

        return $this->rgbToHex($best);
    }

    /**
     * Binary-search a grayscale value for the requested lightness direction.
     *
     * @param float $backgroundLuminance
     * @param float $targetRatio
     * @param string $polarity "dark" or "light".
     * @return string
     */
    private function findGray(float $backgroundLuminance, float $targetRatio, string $polarity): string
    {
        if ($polarity == 'dark') {
            $best = 0;
            $low = 0;
            $high = 255;

            while ($low <= $high) {
                $mid = (int) floor(($low + $high) / 2);
                $contrast = $this->contrastRatioFromLuminance($this->grayToLuminance($mid), $backgroundLuminance);

                if ($contrast >= $targetRatio) {
                    $best = $mid;
                    $low = $mid + 1;
                } else {
                    $high = $mid - 1;
                }
            }

            return $this->grayHex($best);
        }

        $best = 255;
        $low = 0;
        $high = 255;

        while ($low <= $high) {
            $mid = (int) floor(($low + $high) / 2);
            $contrast = $this->contrastRatioFromLuminance($this->grayToLuminance($mid), $backgroundLuminance);

            if ($contrast >= $targetRatio) {
                $best = $mid;
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return $this->grayHex($best);
    }

    /**
     * Calculate WCAG relative luminance for an sRGB color.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $rgb
     * @return float
     */
    private function relativeLuminance(array $rgb): float
    {
        return 0.2126 * $this->linearChannel($rgb['R'])
            + 0.7152 * $this->linearChannel($rgb['G'])
            + 0.0722 * $this->linearChannel($rgb['B']);
    }

    /**
     * Convert one 8-bit sRGB channel to linear light.
     *
     * @param int|float $channel
     * @return float
     */
    private function linearChannel($channel): float
    {
        $value = $channel / 255;

        if ($value <= 0.04045) {
            return $value / 12.92;
        }

        return pow(($value + 0.055) / 1.055, 2.4);
    }

    /**
     * Calculate the WCAG contrast ratio between two relative luminance values.
     *
     * @param float $firstLuminance
     * @param float $secondLuminance
     * @return float
     */
    private function contrastRatioFromLuminance(float $firstLuminance, float $secondLuminance): float
    {
        $lighter = max($firstLuminance, $secondLuminance);
        $darker = min($firstLuminance, $secondLuminance);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Convert a single grayscale channel to relative luminance.
     *
     * @param int $gray
     * @return float
     */
    private function grayToLuminance(int $gray): float
    {
        return $this->relativeLuminance(['R' => $gray, 'G' => $gray, 'B' => $gray]);
    }

    /**
     * Format a grayscale channel as a six-digit hex color.
     *
     * @param int $gray
     * @return string
     */
    private function grayHex(int $gray): string
    {
        $hex = str_pad(dechex($gray), 2, '0', STR_PAD_LEFT);

        return '#' . $hex . $hex . $hex;
    }

    /**
     * Convert an RGB color to HSL for local hue-preserving adjustment.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $rgb
     * @return array{H:float,S:float,L:float}
     */
    private function rgbToHsl(array $rgb): array
    {
        $r = $rgb['R'] / 255;
        $g = $rgb['G'] / 255;
        $b = $rgb['B'] / 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        $lightness = ($max + $min) / 2;
        $hue = 0;
        $saturation = 0;

        if ($delta != 0) {
            $saturation = ($lightness > 0.5)
                ? $delta / (2 - $max - $min)
                : $delta / ($max + $min);

            if ($max == $r) {
                $hue = (($g - $b) / $delta) + (($g < $b) ? 6 : 0);
            } elseif ($max == $g) {
                $hue = (($b - $r) / $delta) + 2;
            } else {
                $hue = (($r - $g) / $delta) + 4;
            }

            $hue *= 60;
        }

        return ['H' => $hue, 'S' => $saturation, 'L' => $lightness];
    }

    /**
     * Convert HSL components back to integer RGB channels.
     *
     * @param float $hue Degrees on the color wheel.
     * @param float $saturation Unit interval saturation.
     * @param float $lightness Unit interval lightness.
     * @return array{R:int,G:int,B:int}
     */
    private function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        $hue = fmod($hue, 360.0);

        if ($hue < 0) {
            $hue += 360.0;
        }

        if ($saturation == 0) {
            $value = (int) round($lightness * 255);

            return ['R' => $value, 'G' => $value, 'B' => $value];
        }

        $q = ($lightness < 0.5)
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - ($lightness * $saturation);
        $p = (2 * $lightness) - $q;
        $h = $hue / 360;

        return [
            'R' => (int) round($this->hueToRgb($p, $q, $h + (1 / 3)) * 255),
            'G' => (int) round($this->hueToRgb($p, $q, $h) * 255),
            'B' => (int) round($this->hueToRgb($p, $q, $h - (1 / 3)) * 255),
        ];
    }

    /**
     * Interpolate one RGB channel for HSL to RGB conversion.
     *
     * @param float $p
     * @param float $q
     * @param float $hue Unit interval hue.
     * @return float
     */
    private function hueToRgb(float $p, float $q, float $hue): float
    {
        if ($hue < 0) {
            $hue++;
        }

        if ($hue > 1) {
            $hue--;
        }

        if (6 * $hue < 1) {
            return $p + (($q - $p) * 6 * $hue);
        }

        if (2 * $hue < 1) {
            return $q;
        }

        if (3 * $hue < 2) {
            return $p + (($q - $p) * ((2 / 3) - $hue) * 6);
        }

        return $p;
    }

    /**
     * Format RGB channels as a normalized six-digit hex color.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $rgb
     * @return string
     */
    private function rgbToHex(array $rgb): string
    {
        return '#' . str_pad(dechex($this->clampChannel($rgb['R'])), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($this->clampChannel($rgb['G'])), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($this->clampChannel($rgb['B'])), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Clamp an RGB channel to the valid 8-bit range.
     *
     * @param int|float $channel
     * @return int
     */
    private function clampChannel($channel): int
    {
        return max(0, min(255, (int) round($channel)));
    }
}
