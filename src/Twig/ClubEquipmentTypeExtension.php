<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\ClubEquipmentType;

class ClubEquipmentTypeExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'club_equipment_type_readable')]
    public function readable(string $equipmentType): string
    {
        return ClubEquipmentType::getReadableValue($equipmentType);
    }
}
