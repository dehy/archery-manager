<?php

declare(strict_types=1);

namespace App\Enum;

enum LicenseeImportNotificationScenario
{
    case NewAccount;
    case Renewal;
    case WelcomeToClub;

    public function emailSubject(): string
    {
        return match ($this) {
            self::NewAccount => 'Votre compte a été créé',
            self::Renewal => 'Votre licence a été synchronisée',
            self::WelcomeToClub => 'Bienvenue au club !',
        };
    }

    public function emailTemplate(): string
    {
        return match ($this) {
            self::NewAccount => 'email_notification/licensee_import_new_account.html.twig',
            self::Renewal => 'email_notification/licensee_import_renewal.html.twig',
            self::WelcomeToClub => 'email_notification/licensee_import_welcome_to_club.html.twig',
        };
    }

    /**
     * Higher values take priority when the same user is notified for several
     * rows in a single import (e.g. a shared account with mixed scenarios).
     */
    public function priority(): int
    {
        return match ($this) {
            self::NewAccount => 3,
            self::Renewal => 2,
            self::WelcomeToClub => 1,
        };
    }
}
