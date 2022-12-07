<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class LoggedInTestCase extends WebTestCase
{
    protected static function createLoggedInAsAdminClient(): KernelBrowser
    {
        $client = parent::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneByEmail('admin@acme.org');
        $client->loginUser($admin);

        return $client;
    }

    protected static function createLoggedInAsUserClient(): KernelBrowser
    {
        $client = parent::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('user1@acme.org');
        $client->loginUser($user);

        return $client;
    }
}
