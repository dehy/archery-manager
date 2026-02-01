<?php

declare(strict_types=1);

namespace App\Controller\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

class MoneyField implements FieldInterface
{
    use FieldTrait;

    protected static IntlMoneyFormatter $moneyFormatter;

    public function __construct()
    {
        $this->dto = new FieldDto();
        $currencies = new ISOCurrencies();

        $numberFormatter = new \NumberFormatter(
            'fr_FR',
            \NumberFormatter::CURRENCY,
        );
        self::$moneyFormatter = new IntlMoneyFormatter(
            $numberFormatter,
            $currencies,
        );
    }

    #[\Override]
    public static function new(
        string $propertyName,
        ?string $label = null,
    ): self {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/crud/fields/money.html.twig')
            ->formatValue(static fn ($property): string => self::$moneyFormatter->format($property));
    }
}
