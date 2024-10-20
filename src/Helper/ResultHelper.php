<?php

declare(strict_types=1);

namespace App\Helper;

class ResultHelper
{
    final public const string HEX_FORMAT = '#%02x%02x%02x';

    final public const string COLOR_LOWEST = '#FFDFD4';

    final public const string COLOR_BEST = '#E31D02';

    public static function colorRatio(float $ratio): string
    {
        [$c1r, $c1g, $c1b] = sscanf(self::COLOR_LOWEST, self::HEX_FORMAT);
        [$c2r, $c2g, $c2b] = sscanf(self::COLOR_BEST, self::HEX_FORMAT);

        // ratio 0 = c1r ; ratio 1 = c2r
        $cfr = round($c1r + ($c2r - $c1r) * $ratio);
        $cfg = round($c1g + ($c2g - $c1g) * $ratio);
        $cfb = round($c1b + ($c2b - $c1b) * $ratio);

        $cfr = min(255, max(0, $cfr));
        $cfg = min(255, max(0, $cfg));
        $cfb = min(255, max(0, $cfb));

        return \sprintf(self::HEX_FORMAT, $cfr, $cfg, $cfb);
    }
}
