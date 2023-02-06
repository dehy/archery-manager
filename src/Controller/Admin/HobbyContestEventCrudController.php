<?php

namespace App\Controller\Admin;

use App\Entity\HobbyContestEvent;

class HobbyContestEventCrudController extends ContestEventCrudController
{
    public static function getEntityFqcn(): string
    {
        return HobbyContestEvent::class;
    }
}
