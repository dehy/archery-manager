<?php

declare(strict_types=1);

namespace App\Api\V1;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * Serializes an enum value as its stable machine code plus its French display label,
 * the label being user-facing content of the club's domain.
 */
final class EnumValue
{
    /**
     * @param class-string<AbstractEnumType<string, string>> $enumType
     *
     * @return array{code: string, label: string}|null
     */
    public static function of(string $enumType, ?string $code): ?array
    {
        if (null === $code) {
            return null;
        }

        return [
            'code' => $code,
            'label' => $enumType::isValueExist($code) ? (string) $enumType::getReadableValue($code) : $code,
        ];
    }

    /**
     * @param class-string<AbstractEnumType<string, string>> $enumType
     * @param list<string>|null                              $codes
     *
     * @return list<array{code: string, label: string}>
     */
    public static function listOf(string $enumType, ?array $codes): array
    {
        $values = [];
        foreach ($codes ?? [] as $code) {
            $values[] = ['code' => $code, 'label' => $enumType::isValueExist($code) ? (string) $enumType::getReadableValue($code) : $code];
        }

        return $values;
    }
}
