<?php

namespace ExpressionEngine\Service\Accessibility\Color;

/**
 * Adjust colors against a contrast target.
 *
 * Implementations may use different contrast units, so callers should obtain
 * the default and normalize targets through the selected algorithm instance.
 */
interface ContrastAlgorithm
{
    /**
     * Return the default target used when a user enables the algorithm without
     * providing a numeric value.
     *
     * @return float
     */
    public function defaultTarget(): float;

    /**
     * Clamp a requested target to the algorithm's supported range.
     *
     * @param float $value
     * @return float
     */
    public function normalizeTarget(float $value): float;

    /**
     * Calculate the contrast value between two colors.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @return float
     */
    public function contrast(array $foregroundRgb, array $backgroundRgb): float;

    /**
     * Find a grayscale foreground color that reaches the requested target.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @param float $target
     * @return string A normalized six-digit hex color including "#".
     */
    public function foregroundForBackground(array $backgroundRgb, float $target): string;

    /**
     * Adjust a foreground color until it reaches the requested target.
     *
     * @param array{R:int|float,G:int|float,B:int|float} $foregroundRgb
     * @param array{R:int|float,G:int|float,B:int|float} $backgroundRgb
     * @param float $target
     * @return string A normalized six-digit hex color including "#".
     */
    public function adjustForegroundForBackground(array $foregroundRgb, array $backgroundRgb, float $target): string;
}
