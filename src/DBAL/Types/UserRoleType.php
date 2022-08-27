<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class UserRoleType extends AbstractEnumType
{
    public const USER = "ROLE_USER";
    public const ADMIN = "ROLE_ADMIN";

    protected static array $choices = [
        self::USER => "Utilisateur",
        self::ADMIN => "Admin",
    ];
}
