<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\ClubEquipmentType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ClubEquipmentTypeExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('club_equipment_type_readable', $this->readable(...)),
        ];
    }

    public function readable(string $equipmentType): string
    {
        return ClubEquipmentType::getReadableValue($equipmentType);
    }
}
