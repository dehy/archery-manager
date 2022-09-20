<?php

namespace App\Controller\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use NumberFormatter;

class MoneyField implements FieldInterface
{
    use FieldTrait;

    protected static IntlMoneyFormatter $moneyFormatter;

    public function __construct()
    {
        $this->dto = new FieldDto();
        $currencies = new ISOCurrencies();

        $numberFormatter = new NumberFormatter(
            'fr_FR',
            NumberFormatter::CURRENCY,
        );
        self::$moneyFormatter = new IntlMoneyFormatter(
            $numberFormatter,
            $currencies,
        );
    }

    public static function new(
        string $propertyName,
        ?string $label = null,
    ): self {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/crud/fields/money.html.twig')
            ->formatValue(function ($property) {
                return self::$moneyFormatter->format($property);
            });
    }
}
