<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Theme of the redesigned interface, chosen in Mon compte → Apparence (assets/styles/next/_themes.scss).
 */
enum InterfaceTheme: string
{
    /** Warm cream, sand sidebar, ink hero card, neutral icons */
    case Soft = 'soft';
    /** Same layout with more colour: orange hero card, one tint per kind of event, coloured navigation icons */
    case Colorful = 'colorful';

    public function label(): string
    {
        return match ($this) {
            self::Soft => 'Doux',
            self::Colorful => 'Coloré',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Soft => 'Sobre et chaleureux. Le thème par défaut.',
            self::Colorful => 'Plus vif : une couleur par type d\'évènement.',
        };
    }
}
