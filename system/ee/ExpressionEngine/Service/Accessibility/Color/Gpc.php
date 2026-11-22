<?php

namespace ExpressionEngine\Service\Accessibility\Color;

/**
 * Calculate and search generic perceptual contrast values for sRGB colors.
 *
 * The base contrast prediction math is polarity-sensitive. Public methods keep
 * the terms "foreground" and "background" so callers do not swap colors and
 * reuse a result without recalculating contrast.
 */
class Gpc implements ContrastAlgorithm
{
    /**
     * Return the default generic perceptual contrast target.
     *
     * @return float
     */
    public function defaultTarget(): float
    {
        return 60.0;
    }

    /**
     * Clamp a requested generic perceptual contrast target.
     *
     * @param float $value
     * @return float
     */
    public function normalizeTarget(float $value): float
    {
        $sign = ($value < 0) ? -1 : 1;

        return $sign * min(108, abs($value));
    }

    /**
     * Calculate the signed perceptual contrast value between two colors.
     *
     * Positive values indicate a darker foreground on a lighter background.
     * Negative values indicate a lighter foreground on a darker background.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @return float
     */
    public function contrast(array $foregroundRgb, array $backgroundRgb): float
    {
        return $this->contrastFromLuminance(
            $this->sRgbToY($foregroundRgb),
            $this->sRgbToY($backgroundRgb)
        );
    }

    /**
     * Calculate the signed perceptual contrast value between two colors.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @return float
     */
    public function lightnessContrast(array $foregroundRgb, array $backgroundRgb): float
    {
        return $this->contrast($foregroundRgb, $backgroundRgb);
    }

    /**
     * Find a grayscale foreground color that reaches the requested target.
     *
     * Positive targets allow either readable polarity and prefer the practical
     * polarity for the given background. Negative targets request a light
     * foreground on a dark background. When the exact target cannot be reached,
     * the method returns the highest contrast grayscale endpoint.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @param float $target
     * @return string A normalized six-digit hex color including "#".
     */
    public function foregroundForBackground(array $backgroundRgb, float $target = 60): string
    {
        if ($target == 0) {
            return $this->rgbToHex($backgroundRgb);
        }

        $backgroundY = $this->sRgbToY($backgroundRgb);

        return $this->findForeground($backgroundY, $this->normalizeTarget($target));
    }

    /**
     * Adjust a foreground color until it reaches the requested contrast target.
     *
     * The method first returns the foreground unchanged when it already satisfies
     * the target. Otherwise it searches HSL lightness while preserving the
     * foreground hue and saturation, tries the opposite polarity if needed, and
     * finally falls back to a grayscale search.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @param float $target
     * @return string A normalized six-digit hex color including "#".
     */
    public function adjustForegroundForBackground(array $foregroundRgb, array $backgroundRgb, float $target = 60): string
    {
        $target = $this->normalizeTarget($target);

        if ($target == 0) {
            return $this->rgbToHex($foregroundRgb);
        }

        $minimum = abs($target);
        $current = $this->contrast($foregroundRgb, $backgroundRgb);

        if ($this->contrastMeetsTarget($current, $target)) {
            return $this->rgbToHex($foregroundRgb);
        }

        $backgroundY = $this->sRgbToY($backgroundRgb);
        $preferredPolarity = $this->preferredPolarity($current, $backgroundY, $target);
        $adjusted = $this->adjustLightness($foregroundRgb, $backgroundY, $minimum, $preferredPolarity);

        if ($adjusted !== false) {
            return $adjusted;
        }

        $oppositePolarity = ($preferredPolarity == 'light') ? 'dark' : 'light';
        $adjusted = $this->adjustLightness($foregroundRgb, $backgroundY, $minimum, $oppositePolarity);

        if ($adjusted !== false) {
            return $adjusted;
        }

        $fallbackTarget = ($preferredPolarity == 'light') ? -$minimum : $minimum;

        return $this->findForeground($backgroundY, $fallbackTarget);
    }

    /**
     * Search for a grayscale foreground against a known background luminance.
     *
     * @param float $backgroundY
     * @param float $target
     * @return string
     */
    private function findForeground(float $backgroundY, float $target): string
    {
        $minimum = abs($target);
        $blackContrast = $this->contrastFromLuminance($this->grayToY(0), $backgroundY);
        $whiteContrast = $this->contrastFromLuminance($this->grayToY(255), $backgroundY);
        $blackCapacity = max(0, $blackContrast);
        $whiteCapacity = max(0, -$whiteContrast);

        if ($target < 0 && $whiteCapacity >= $minimum) {
            return $this->findGray($backgroundY, $minimum, 'light');
        }

        if ($blackCapacity >= $minimum && $whiteCapacity >= $minimum) {
            $midY = $this->grayToY(128);
            $polarity = ($backgroundY >= $midY) ? 'dark' : 'light';

            return $this->findGray($backgroundY, $minimum, $polarity);
        }

        if ($blackCapacity >= $minimum) {
            return $this->findGray($backgroundY, $minimum, 'dark');
        }

        if ($whiteCapacity >= $minimum) {
            return $this->findGray($backgroundY, $minimum, 'light');
        }

        return ($blackCapacity >= $whiteCapacity) ? '#000000' : '#ffffff';
    }

    /**
     * Determine whether a signed contrast value satisfies a target.
     *
     * Negative targets require light-on-dark polarity. Positive targets are
     * treated as a non-polar minimum and accept either sign.
     *
     * @param float $contrast
     * @param float $target
     * @return bool
     */
    private function contrastMeetsTarget(float $contrast, float $target): bool
    {
        $minimum = abs($target);

        if ($target < 0) {
            return -$contrast >= $minimum;
        }

        return abs($contrast) >= $minimum;
    }

    /**
     * Choose the first polarity to search for a color adjustment.
     *
     * Existing non-zero contrast keeps its current polarity. A negative target
     * explicitly requests a light foreground, and zero contrast falls back to the
     * practical polarity for the background lightness.
     *
     * @param float $currentContrast
     * @param float $backgroundY
     * @param float $target
     * @return string "dark" or "light".
     */
    private function preferredPolarity(float $currentContrast, float $backgroundY, float $target): string
    {
        if ($target < 0) {
            return 'light';
        }

        if ($currentContrast > 0) {
            return 'dark';
        }

        if ($currentContrast < 0) {
            return 'light';
        }

        return ($backgroundY >= $this->grayToY(128)) ? 'dark' : 'light';
    }

    /**
     * Search HSL lightness for the smallest change that reaches the target.
     *
     * Hue and saturation are held constant so modifier-generated colors keep
     * their intended color family whenever the contrast target can be met that
     * way.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $rgb
     * @param float $backgroundY
     * @param float $target
     * @param string $polarity "dark" or "light".
     * @return string|false
     */
    private function adjustLightness(array $rgb, float $backgroundY, float $target, string $polarity)
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
            $contrast = $this->contrastFromLuminance($this->sRgbToY($candidateRgb), $backgroundY);
            $meets = ($polarity == 'dark') ? ($contrast >= $target) : (-$contrast >= $target);

            if ($meets) {
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
     * Binary-search a grayscale value for the requested polarity and contrast.
     *
     * @param float $backgroundY
     * @param float $target
     * @param string $polarity "dark" or "light".
     * @return string
     */
    private function findGray(float $backgroundY, float $target, string $polarity): string
    {
        if ($polarity == 'dark') {
            $best = 0;
            $low = 0;
            $high = 255;

            while ($low <= $high) {
            $mid = (int) floor(($low + $high) / 2);
                $contrast = $this->contrastFromLuminance($this->grayToY($mid), $backgroundY);

                if ($contrast >= $target) {
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
            $contrast = -$this->contrastFromLuminance($this->grayToY($mid), $backgroundY);

            if ($contrast >= $target) {
                $best = $mid;
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return $this->grayHex($best);
    }

    /**
     * Convert sRGB channel values to screen luminance (Y).
     *
     * Constants are the sRGB coefficients and transfer exponent for the base
     * perceptual contrast prediction equation.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $rgb
     * @return float
     */
    private function sRgbToY(array $rgb): float
    {
        return 0.2126729 * pow($rgb['R'] / 255, 2.4)
            + 0.7151522 * pow($rgb['G'] / 255, 2.4)
            + 0.0721750 * pow($rgb['B'] / 255, 2.4);
    }

    /**
     * Convert a single grayscale channel to screen luminance (Y).
     *
     * @param int $gray
     * @return float
     */
    private function grayToY(int $gray): float
    {
        return $this->sRgbToY(['R' => $gray, 'G' => $gray, 'B' => $gray]);
    }

    /**
     * Apply the base perceptual contrast prediction equation.
     *
     * The low-end black clamp, minimum delta, exponents, scale, and offsets are
     * the sRGB constants used by the prediction equation.
     *
     * @param float $textY
     * @param float $backgroundY
     * @return float
     */
    private function contrastFromLuminance(float $textY, float $backgroundY): float
    {
        $blackThreshold = 0.022;
        $blackClamp = 1.414;

        $textY = ($textY > $blackThreshold)
            ? $textY
            : $textY + pow($blackThreshold - $textY, $blackClamp);
        $backgroundY = ($backgroundY > $blackThreshold)
            ? $backgroundY
            : $backgroundY + pow($blackThreshold - $backgroundY, $blackClamp);

        if (abs($backgroundY - $textY) < 0.0005) {
            return 0.0;
        }

        if ($backgroundY > $textY) {
            $contrast = (pow($backgroundY, 0.56) - pow($textY, 0.57)) * 1.14;

            return ($contrast < 0.1) ? 0.0 : ($contrast - 0.027) * 100;
        }

        $contrast = (pow($backgroundY, 0.65) - pow($textY, 0.62)) * 1.14;

        return ($contrast > -0.1) ? 0.0 : ($contrast + 0.027) * 100;
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
        return '#' . str_pad(dechex((int) $rgb['R']), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex((int) $rgb['G']), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex((int) $rgb['B']), 2, '0', STR_PAD_LEFT);
    }
}
