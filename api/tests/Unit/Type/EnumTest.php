<?php

namespace App\Tests\Unit\Type;

use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    /**
     * @dataProvider enumProvider
     */
    public function testEnumCases(string $enumClass, array $expectedCases): void
    {
        $cases = array_map(fn($c) => $c->value, $enumClass::cases());
        $this->assertEqualsCanonicalizing($expectedCases, $cases);
    }

    public static function enumProvider(): array
    {
        return [
            [\App\Type\ArrowType::class, ['wood', 'aluminum', 'carbon', 'aluminum_carbon']],
            [\App\Type\BowType::class, ['recurve', 'compound', 'barebow', 'longbow']],
            [\App\Type\ContestType::class, ['individual', 'team', 'mixed_team']],
            [\App\Type\DisciplineType::class, ['target', 'indoor', 'field', 'nature', '3d', 'para', 'run']],
            [\App\Type\EventParticipationStateType::class, ['registered', 'checked_in', 'absent', 'disqualified', 'finished']],
            [\App\Type\EventStatusType::class, ['scheduled', 'ongoing', 'completed', 'cancelled']],
            [\App\Type\FletchingType::class, ['plastic', 'feather']],
            [\App\Type\GenderType::class, ['male', 'female', 'other']],
            [\App\Type\LicenseActivityType::class, ['competition', 'leisure', 'training']],
            [\App\Type\LicenseAgeCategoryType::class, ['junior', 'senior', 'veteran']],
            [\App\Type\LicenseCategoryType::class, ['adult', 'youth', 'para']],
            [\App\Type\LicenseType::class, ['annual', 'temporary', 'guest']],
            [\App\Type\PracticeLevelType::class, ['beginner', 'intermediate', 'advanced']],
            [\App\Type\SportType::class, ['archery', 'crossbow']],
            [\App\Type\TargetTypeType::class, ['standard', '3d', 'field']],
        ];
    }
}
