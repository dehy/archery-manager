<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseApplicationStatusType extends AbstractEnumType
{
    public const string PENDING = 'pending';

    public const string VALIDATED = 'validated';

    public const string WAITING_LIST = 'waiting_list';

    public const string REJECTED = 'rejected';

    protected static array $choices = [
        self::PENDING => 'En attente',
        self::VALIDATED => 'Validée',
        self::WAITING_LIST => "Liste d'attente",
        self::REJECTED => 'Refusée',
    ];
}
