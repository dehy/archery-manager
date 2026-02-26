<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\IpAnonymizer;
use PHPUnit\Framework\TestCase;

final class IpAnonymizerTest extends TestCase
{
    private IpAnonymizer $anonymizer;

    protected function setUp(): void
    {
        $this->anonymizer = new IpAnonymizer();
    }

    // ── Empty / invalid ────────────────────────────────────────────────

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', $this->anonymizer->anonymize(''));
    }

    public function testMalformedIpReturnsEmpty(): void
    {
        $this->assertSame('', $this->anonymizer->anonymize('not-an-ip'));
    }

    public function testPartialIpReturnsEmpty(): void
    {
        $this->assertSame('', $this->anonymizer->anonymize('192.168.1'));
    }

    public function testTextWithColonsReturnsEmpty(): void
    {
        // Looks like IPv6 (contains ':') but is not a valid address.
        $this->assertSame('', $this->anonymizer->anonymize('not:valid:ipv6'));
    }

    // ── IPv4 ───────────────────────────────────────────────────────────

    public function testIpv4LastOctetZeroed(): void
    {
        $this->assertSame('192.168.1.0', $this->anonymizer->anonymize('192.168.1.100'));
    }

    public function testIpv4AlreadyZeroedLastOctet(): void
    {
        $this->assertSame('10.0.0.0', $this->anonymizer->anonymize('10.0.0.255'));
    }

    public function testIpv4Loopback(): void
    {
        $this->assertSame('127.0.0.0', $this->anonymizer->anonymize('127.0.0.1'));
    }

    public function testIpv4AllOctets(): void
    {
        $this->assertSame('255.255.255.0', $this->anonymizer->anonymize('255.255.255.255'));
    }

    // ── IPv6 ───────────────────────────────────────────────────────────

    public function testIpv6Last80BitsZeroed(): void
    {
        // 2001:0db8:85a3:0000:0000:8a2e:0370:7334
        // Keep first 48 bits (2001:0db8:85a3), zero the rest (80 bits = last 10 bytes).
        $this->assertSame(
            '2001:db8:85a3::',
            $this->anonymizer->anonymize('2001:0db8:85a3:0000:0000:8a2e:0370:7334'),
        );
    }

    public function testIpv6WithCompressedNotation(): void
    {
        $this->assertSame(
            '2001:db8:85a3::',
            $this->anonymizer->anonymize('2001:db8:85a3::8a2e:370:7334'),
        );
    }

    public function testIpv6Loopback(): void
    {
        // ::1 → first 6 bytes are 0x000000000000, last 10 zeroed → '::'
        $this->assertSame('::', $this->anonymizer->anonymize('::1'));
    }

    public function testIpv6AllBitsSet(): void
    {
        $this->assertSame(
            'ffff:ffff:ffff::',
            $this->anonymizer->anonymize('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'),
        );
    }
}
