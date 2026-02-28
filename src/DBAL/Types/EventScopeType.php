<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventScopeType extends AbstractEnumType
{
    public const string CLUB = 'club';

    public const string DEPARTMENTAL = 'departmental';

    public const string REGIONAL = 'regional';

    public const string NATIONAL = 'national';

    protected static array $choices = [
        self::CLUB => 'Club',
        self::DEPARTMENTAL => 'Comité départemental',
        self::REGIONAL => 'Comité régional',
        self::NATIONAL => 'National',
    ];
}
