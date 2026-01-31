<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class ClubEquipmentType extends AbstractEnumType
{
    public const string BOW = 'bow';

    public const string ARROWS = 'arrows';

    public const string QUIVER = 'quiver';

    public const string ARMGUARD = 'armguard';

    public const string FINGER_TAB = 'finger_tab';

    public const string CHEST_GUARD = 'chest_guard';

    public const string OTHER = 'other';

    protected static array $choices = [
        self::BOW => 'Arc',
        self::ARROWS => 'Flèches',
        self::QUIVER => 'Carquois',
        self::ARMGUARD => 'Palette',
        self::FINGER_TAB => 'Palette de tir',
        self::CHEST_GUARD => 'Plastron',
        self::OTHER => 'Autre',
    ];
}
