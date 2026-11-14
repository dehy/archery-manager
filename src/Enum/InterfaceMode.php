<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Light or dark version of the theme of the redesigned interface. "System" follows the device
 * (prefers-color-scheme), in CSS only: the server never knows which one the device uses.
 */
enum InterfaceMode: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Système',
            self::Light => 'Clair',
            self::Dark => 'Sombre',
        };
    }

    public function help(): string
    {
        return match ($this) {
            self::System => 'Le mode suit le réglage de votre appareil.',
            self::Light => 'Le mode clair reste actif, quel que soit le réglage de votre appareil.',
            self::Dark => 'Le mode sombre reste actif, quel que soit le réglage de votre appareil.',
        };
    }
}
