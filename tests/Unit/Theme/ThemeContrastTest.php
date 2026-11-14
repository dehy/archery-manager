<?php

declare(strict_types=1);

namespace App\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * WCAG contrast of the colour tokens of the redesigned interface, in the four looks (two themes, light and dark).
 * The tokens are read from assets/styles/next/_tokens.scss, the only place they are written.
 */
final class ThemeContrastTest extends TestCase
{
    private const string TOKENS = __DIR__.'/../../../assets/styles/next/_tokens.scss';

    /** Text: WCAG AA */
    private const float TEXT = 4.5;

    /** Icons, field borders, focus marks: WCAG 1.4.11 */
    private const float NON_TEXT = 3.0;

    /** Look => base token set */
    private const array LOOKS = [
        'soft' => 'light',
        'colorful' => 'light',
        'soft-dark' => 'dark',
        'colorful-dark' => 'dark',
    ];

    /**
     * [foreground, background, minimum ratio, what it is]. A transparent background is read over the third
     * element of the background list (what is behind it); a translucent one is blended over it.
     *
     * @var list<array{string, string|list<string>, float, string}>
     */
    private const array PAIRS = [
        ['ink', 'ground', self::TEXT, 'text on the page'],
        ['ink', 'surface', self::TEXT, 'text on a card'],
        ['ink', 'sand', self::TEXT, 'text on the sidebar'],
        ['soft', 'ground', self::TEXT, 'secondary text on the page'],
        ['soft', 'surface', self::TEXT, 'secondary text on a card'],
        ['soft', 'sand', self::TEXT, 'secondary text on the sidebar'],
        ['no', 'sand', self::TEXT, 'sidebar links'],
        ['accent-text', 'ground', self::TEXT, 'links on the page'],
        ['accent-text', 'surface', self::TEXT, 'links on a card'],
        ['accent-text', 'accent-bg', self::TEXT, 'accent alert'],
        ['link-hover', 'surface', self::TEXT, 'hovered link'],
        ['on-accent', 'accent', self::TEXT, 'primary button'],
        ['on-accent', 'accent-hover', self::TEXT, 'hovered primary button'],
        ['on-ink', 'ink', self::TEXT, 'dark button'],
        ['tag', 'sand', self::TEXT, 'tag'],
        ['ok', 'ok-bg', self::TEXT, 'status ok'],
        ['maybe', 'maybe-bg', self::TEXT, 'status maybe'],
        ['no', 'no-bg', self::TEXT, 'status no'],
        ['bad', 'bad-bg', self::TEXT, 'status bad'],
        ['info', 'info-bg', self::TEXT, 'status info'],
        ['soft', 'weekday', self::TEXT, 'calendar weekday'],
        ['soft', ['outside', 'surface'], self::TEXT, 'calendar day of another month'],
        ['field', 'surface', self::NON_TEXT, 'form field border'],
        ['accent', 'ground', self::NON_TEXT, 'focus ring'],
        ['hero-fg', 'hero-bg', self::TEXT, 'next event title'],
        ['hero-eyebrow', 'hero-bg', self::TEXT, 'next event label'],
        ['hero-meta', 'hero-bg', self::TEXT, 'next event details'],
        ['hero-tile-fg', ['hero-tile-bg', 'hero-bg'], self::TEXT, 'next event date'],
        ['hero-tile-num', ['hero-tile-bg', 'hero-bg'], self::TEXT, 'next event day'],
        ['hero-btn-fg', 'hero-btn-bg', self::TEXT, 'button on the next event'],
        ['hero-btn-fg', 'hero-btn-hover-bg', self::TEXT, 'hovered button on the next event'],
        ['hero-outline', 'hero-bg', self::TEXT, 'outline button on the next event'],
        ['hero-outline-hover-fg', 'hero-outline', self::TEXT, 'hovered outline button on the next event'],
        ['nav-current-fg', ['nav-current-bg', 'surface'], self::NON_TEXT, 'current sidebar icon'],
        ['nav-1-fg', ['nav-1-bg', 'sand'], self::NON_TEXT, 'sidebar icon 1'],
        ['nav-2-fg', ['nav-2-bg', 'sand'], self::NON_TEXT, 'sidebar icon 2'],
        ['nav-3-fg', ['nav-3-bg', 'sand'], self::NON_TEXT, 'sidebar icon 3'],
        ['nav-4-fg', ['nav-4-bg', 'sand'], self::NON_TEXT, 'sidebar icon 4'],
        ['nav-5-fg', ['nav-5-bg', 'sand'], self::NON_TEXT, 'sidebar icon 5'],
        ['advice-fg', 'advice-bg', self::TEXT, 'advice card'],
        ['advice-sub', 'advice-bg', self::TEXT, 'advice card date'],
        ['kind-contest-fg', 'kind-contest-bg', self::TEXT, 'contest in the calendar'],
        ['kind-training-fg', 'kind-training-bg', self::TEXT, 'training in the calendar'],
        ['kind-free-fg', 'kind-free-bg', self::TEXT, 'free training in the calendar'],
        ['kind-other-fg', 'kind-other-bg', self::TEXT, 'other event in the calendar'],
        ['tile-contest-fg', ['tile-contest-bg', 'surface'], self::TEXT, 'contest date'],
        ['tile-contest-sub', ['tile-contest-bg', 'surface'], self::TEXT, 'contest month'],
        ['tile-training-fg', ['tile-training-bg', 'surface'], self::TEXT, 'training date'],
        ['tile-training-sub', ['tile-training-bg', 'surface'], self::TEXT, 'training month'],
        ['tile-free-fg', ['tile-free-bg', 'surface'], self::TEXT, 'free training date'],
        ['tile-free-sub', ['tile-free-bg', 'surface'], self::TEXT, 'free training month'],
        ['tile-other-fg', ['tile-other-bg', 'surface'], self::TEXT, 'other event date'],
        ['tile-other-sub', ['tile-other-bg', 'surface'], self::TEXT, 'other event month'],
    ];

    /** @var array<string, array<string, string>>|null */
    private static ?array $maps = null;

    /**
     * @param string|list<string> $background
     */
    #[DataProvider('pairs')]
    public function testTheTokenPairIsReadable(string $look, string $foreground, string|array $background, float $minimum, string $what): void
    {
        $tokens = $this->tokens($look);
        $fgValue = $tokens[$foreground] ?? null;
        $this->assertNotNull($fgValue, \sprintf('Token "%s" missing in the %s look', $foreground, $look));
        if ('currentColor' === $fgValue) {
            $this->addToAssertionCount(1); // inherits the link colour, checked by the sidebar link pair

            return;
        }

        $behind = \is_array($background) ? $background : [$background];
        $backgroundRgb = $this->colour($tokens, array_pop($behind), null);
        foreach (array_reverse($behind) as $layer) {
            $backgroundRgb = $this->colour($tokens, $layer, $backgroundRgb);
        }

        $ratio = $this->ratio($this->colour($tokens, $foreground, $backgroundRgb), $backgroundRgb);
        $this->assertGreaterThanOrEqual(
            $minimum,
            round($ratio, 2),
            \sprintf('%s (%s on %s) in the %s look: %.2f:1, needs %.1f:1', $what, $foreground, implode(' over ', (array) $background), $look, $ratio, $minimum),
        );
    }

    public function testEveryLookHasTheSameTokens(): void
    {
        $names = null;
        foreach (array_keys(self::LOOKS) as $look) {
            $lookNames = array_keys($this->maps()['look-'.$look]);
            sort($lookNames);
            $names ??= $lookNames;
            $this->assertSame($names, $lookNames, \sprintf('The %s look does not define the same tokens as the others', $look));
        }

        $light = array_keys($this->maps()['base-light']);
        $dark = array_keys($this->maps()['base-dark']);
        sort($light);
        sort($dark);
        $this->assertSame($light, $dark, 'The light and dark base tokens differ');
    }

    /**
     * @return iterable<string, array{string, string, string|list<string>, float, string}>
     */
    public static function pairs(): iterable
    {
        foreach (array_keys(self::LOOKS) as $look) {
            foreach (self::PAIRS as [$foreground, $background, $minimum, $what]) {
                yield \sprintf('%s: %s', $look, $what) => [$look, $foreground, $background, $minimum, $what];
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function tokens(string $look): array
    {
        return $this->maps()['look-'.$look] + $this->maps()['base-'.self::LOOKS[$look]];
    }

    /**
     * Maps of _tokens.scss: `$am-<base|look>-<name>: (` … `);`, one `name: value,` per line.
     *
     * @return array<string, array<string, string>>
     */
    private function maps(): array
    {
        if (null === self::$maps) {
            $scss = (string) file_get_contents(self::TOKENS);
            preg_match_all('/^\$am-((?:base|look)-[a-z-]+): \((.*?)^\);/ms', $scss, $blocks, \PREG_SET_ORDER);
            self::$maps = [];
            foreach ($blocks as [, $name, $body]) {
                preg_match_all('/^\s*([a-z0-9-]+):\s*(.+?),\s*(?:\/\/.*)?$/m', $body, $entries, \PREG_SET_ORDER);
                self::$maps[$name] = array_column($entries, 2, 1);
            }
        }

        return self::$maps;
    }

    /**
     * Opaque RGB of a token, a translucent or transparent one blended over what is behind it.
     *
     * @param array<string, string>     $tokens
     * @param array{float, float, float}|null $behind
     *
     * @return array{float, float, float}
     */
    private function colour(array $tokens, string $name, ?array $behind): array
    {
        $value = $tokens[$name] ?? null;
        $this->assertNotNull($value, \sprintf('Token "%s" missing', $name));

        if ('transparent' === $value) {
            $this->assertNotNull($behind, \sprintf('Nothing behind the transparent token "%s"', $name));

            return $behind;
        }

        if (1 === preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $value, $hex)) {
            return [(float) hexdec($hex[1]), (float) hexdec($hex[2]), (float) hexdec($hex[3])];
        }

        $this->assertSame(1, preg_match('/^rgba?\(\s*([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?\s*\)$/', $value, $rgb), \sprintf('Token "%s" is not a colour: %s', $name, $value));
        $alpha = isset($rgb[4]) ? (float) $rgb[4] : 1.0;
        $colour = [(float) $rgb[1], (float) $rgb[2], (float) $rgb[3]];
        if ($alpha >= 1.0) {
            return $colour;
        }

        $this->assertNotNull($behind, \sprintf('Nothing behind the translucent token "%s"', $name));

        return [
            $colour[0] * $alpha + $behind[0] * (1 - $alpha),
            $colour[1] * $alpha + $behind[1] * (1 - $alpha),
            $colour[2] * $alpha + $behind[2] * (1 - $alpha),
        ];
    }

    /**
     * @param array{float, float, float} $first
     * @param array{float, float, float} $second
     */
    private function ratio(array $first, array $second): float
    {
        $lighter = max($this->luminance($first), $this->luminance($second));
        $darker = min($this->luminance($first), $this->luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * @param array{float, float, float} $rgb
     */
    private function luminance(array $rgb): float
    {
        [$red, $green, $blue] = array_map(static function (float $channel): float {
            $channel /= 255;

            return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }
}
