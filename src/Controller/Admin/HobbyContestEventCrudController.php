<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\HobbyContestEvent;

class HobbyContestEventCrudController extends ContestEventCrudController
{
    #[\Override]
    public static function getEntityFqcn(): string
    {
        return HobbyContestEvent::class;
    }
}
