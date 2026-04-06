<?php

declare(strict_types=1);

namespace App\Tests\application\Controller\Admin;

use App\Controller\Admin\UserCrudController;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserCrudControllerTest extends TestCase
{
    public function testCreateEntitySetsStringPasswordAndDefaultRole(): void
    {
        $controller = new UserCrudController();

        /** @var User $user */
        $user = $controller->createEntity(User::class);

        $this->assertInstanceOf(User::class, $user);
        $this->assertIsString($user->getPassword());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}
