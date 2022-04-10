<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class FletchingType extends AbstractEnumType
{
    public final const PLASTIC = 'plastic';
    public final const SPINWINGS = 'spinwings';

    protected static array $choices = [
        self::PLASTIC => "Plastique",
        self::SPINWINGS => "Spinwings",
    ];
}