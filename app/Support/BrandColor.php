<?php

namespace App\Support;

/**
 * Readability checks for an organization's brand color, which is used for
 * button fills, active tabs and selection marks on both light and dark pages.
 */
class BrandColor
{
    /** The page background in light mode. */
    private const LIGHT_PAPER = '#F6F1E7';

    /** The page background in dark mode. */
    private const DARK_PAPER = '#0F1620';

    private const LIGHT_TEXT = '#FFFFFF';

    private const DARK_TEXT = '#1C2430';

    /** WCAG AA for text on a button fill. */
    private const MINIMUM_TEXT_CONTRAST = 4.5;

    /** WCAG AA for marks and underlines against the page. */
    private const MINIMUM_MARK_CONTRAST = 3.0;

    public function __construct(private string $hex) {}

    /**
     * The text color that reads best on top of the brand color.
     */
    public function textColor(): string
    {
        return self::contrast($this->hex, self::LIGHT_TEXT) >= self::contrast($this->hex, self::DARK_TEXT)
            ? self::LIGHT_TEXT
            : self::DARK_TEXT;
    }

    public function isReadable(): bool
    {
        return self::contrast($this->hex, $this->textColor()) >= self::MINIMUM_TEXT_CONTRAST
            && self::contrast($this->hex, self::LIGHT_PAPER) >= self::MINIMUM_MARK_CONTRAST
            && self::contrast($this->hex, self::DARK_PAPER) >= self::MINIMUM_MARK_CONTRAST;
    }

    /**
     * The closest readable color with the same hue, found by stepping the
     * lightness up and down. Returns null if no lightness works.
     */
    public function nearestReadable(): ?string
    {
        if ($this->isReadable()) {
            return mb_strtoupper($this->hex);
        }

        [$hue, $saturation, $lightness] = self::toHsl($this->hex);

        for ($step = 0.01; $step <= 1; $step += 0.01) {
            foreach ([$lightness - $step, $lightness + $step] as $candidateLightness) {
                if ($candidateLightness < 0 || $candidateLightness > 1) {
                    continue;
                }

                $candidate = new self(self::fromHsl($hue, $saturation, $candidateLightness));

                if ($candidate->isReadable()) {
                    return $candidate->hex;
                }
            }
        }

        return null;
    }

    private static function contrast(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function luminance(string $hex): float
    {
        [$red, $green, $blue] = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, self::toRgb($hex));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * @return array{int, int, int}
     */
    private static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (mb_strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [(int) hexdec(mb_substr($hex, 0, 2)), (int) hexdec(mb_substr($hex, 2, 2)), (int) hexdec(mb_substr($hex, 4, 2))];
    }

    /**
     * @return array{float, float, float}
     */
    private static function toHsl(string $hex): array
    {
        [$red, $green, $blue] = array_map(fn (int $channel): float => $channel / 255, self::toRgb($hex));
        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $lightness];
        }

        $delta = $max - $min;
        $saturation = $lightness > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

        $hue = match ($max) {
            $red => (($green - $blue) / $delta) + ($green < $blue ? 6 : 0),
            $green => (($blue - $red) / $delta) + 2,
            default => (($red - $green) / $delta) + 4,
        };

        return [$hue / 6, $saturation, $lightness];
    }

    private static function fromHsl(float $hue, float $saturation, float $lightness): string
    {
        if ($saturation === 0.0) {
            $red = $green = $blue = $lightness;
        } else {
            $q = $lightness < 0.5 ? $lightness * (1 + $saturation) : $lightness + $saturation - $lightness * $saturation;
            $p = 2 * $lightness - $q;
            $red = self::hueToChannel($p, $q, $hue + 1 / 3);
            $green = self::hueToChannel($p, $q, $hue);
            $blue = self::hueToChannel($p, $q, $hue - 1 / 3);
        }

        return sprintf('#%02X%02X%02X', (int) round($red * 255), (int) round($green * 255), (int) round($blue * 255));
    }

    private static function hueToChannel(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }

        if ($t > 1) {
            $t -= 1;
        }

        return match (true) {
            $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
            $t < 1 / 2 => $q,
            $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
            default => $p,
        };
    }
}
