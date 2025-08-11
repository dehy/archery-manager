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
            [\App\Type\BowType::class, ['recurve', 'compound', 'traditional', 'barebow']],
            [\App\Type\ContestType::class, ['federal', 'international', 'challenge33', 'individual', 'team']],
            [\App\Type\DisciplineType::class, ['target', 'indoor', 'field', 'nature', '3d', 'para', 'run']],
            [\App\Type\EventParticipationStateType::class, ['not_going', 'interested', 'registered']],
            [\App\Type\EventStatusType::class, ['cancelled', 'postponed', 'rescheduled', 'scheduled']],
            [\App\Type\FletchingType::class, ['plastic', 'spinwings']],
            [\App\Type\GenderType::class, ['male', 'female', 'other']],
            [\App\Type\LicenseActivityType::class, ['AC', 'AD', 'BB', 'CL', 'CO', 'TL']],
            [\App\Type\LicenseAgeCategoryType::class, ['S1', 'S2', 'S3', 'U11', 'U13', 'U15', 'U18', 'U21', 'P', 'B', 'M', 'C', 'J', 'S', 'V', 'SV']],
            [\App\Type\LicenseCategoryType::class, ['P', 'J', 'A']],
            [\App\Type\LicenseType::class, ['P', 'J', 'A', 'L', 'E', 'S', 'U', 'H', 'D']],
            [\App\Type\PracticeLevelType::class, ['beginner', 'intermediate', 'advanced']],
            [\App\Type\SportType::class, ['archery']],
            [\App\Type\TargetTypeType::class, ['monospot', 'trispot', 'field', 'animal', 'beursault']],
        ];
    }
}
