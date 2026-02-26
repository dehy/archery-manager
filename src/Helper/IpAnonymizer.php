<?php

declare(strict_types=1);

namespace App\Helper;

final class IpAnonymizer
{
    public function anonymize(string $ip): string
    {
        if ('' === $ip) {
            return '';
        }

        if (str_contains($ip, ':')) {
            // IPv6: zero the last 80 bits (10 bytes) of the 16-byte address
            $packed = inet_pton($ip);
            if (false !== $packed) {
                $anonymized = inet_ntop(substr($packed, 0, 6).str_repeat("\x00", 10));
                $result = $anonymized ?: '';
            } else {
                $result = '';
            }
        } else {
            // IPv4: zero the last octet
            $parts = explode('.', $ip);
            $result = '';
            if (4 === \count($parts)) {
                $parts[3] = '0';
                $result = implode('.', $parts);
            }
        }

        return $result;
    }
}
