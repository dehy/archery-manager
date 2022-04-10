<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class ArrowType extends AbstractEnumType
{
    public final const WOOD = 'wood';
    public final const ALUMINUM = 'aluminum';
    public final const CARBON = 'carbon';
    public final const ALUMINUM_CARBON = 'aluminum_carbon';

    protected static array $choices = [
        self::WOOD => "Bois",
        self::ALUMINUM => "Aluminium",
        self::CARBON => "Carbone",
        self::ALUMINUM_CARBON => "Aluminium Carbone",
    ];
}