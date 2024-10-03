<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class UserRoleType extends AbstractEnumType
{
    public const USER = 'ROLE_USER';
    public const COACH = 'ROLE_COACH';
    public const CLUB_ADMIN = 'ROLE_CLUB_ADMIN';
    public const ADMIN = 'ROLE_ADMIN';

    protected static array $choices = [
        self::USER => 'Utilisateur',
        self::COACH => 'Entraîneur',
        self::CLUB_ADMIN => 'Admin du club',
        self::ADMIN => 'Admin système',
    ];
}
