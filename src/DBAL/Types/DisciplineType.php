<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class DisciplineType extends AbstractEnumType
{
    public final const TARGET = 'target';
    public final const INDOOR = 'indoor';
    public final const FIELD = 'field';
    public final const NATURE = 'nature';
    public final const THREE_D = '3d';
    public final const PARA = 'para';
    public final const RUN = 'run';

    protected static array $choices = [
        self::TARGET => 'Extérieur',
        self::INDOOR => 'Salle',
        self::FIELD => 'Campagne',
        self::NATURE => 'Nature',
        self::THREE_D => '3D',
        self::PARA => 'Handi',
        self::RUN => 'Run Archery',
    ];
}