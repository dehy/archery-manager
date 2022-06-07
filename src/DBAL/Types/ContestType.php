<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class ContestType extends AbstractEnumType
{
    public const FEDERAL = "federal";
    public const INTERNATIONAL = "international";
    public const CHALLENGE33 = "challenge33";

    protected static array $choices = [
        self::FEDERAL => "Fédéral",
        self::INTERNATIONAL => "International",
        self::CHALLENGE33 => "Challenge 33",
    ];
}
