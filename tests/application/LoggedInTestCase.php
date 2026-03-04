<?php

declare(strict_types=1);

namespace App\Tests\application;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
class LoggedInTestCase extends WebTestCase
{
    protected static function createLoggedInAsAdminClient(): KernelBrowser
    {
        $client = parent::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneByEmail('admin@acme.org');
        $client->loginUser($admin);

        return $client;
    }

    protected static function createLoggedInAsUserClient(): KernelBrowser
    {
        $client = parent::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('user1@ladg.com');
        $client->loginUser($user);

        return $client;
    }

    protected static function createLoggedInAsClubAdminClient(): KernelBrowser
    {
        $client = parent::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $clubAdmin = $userRepository->findOneByEmail('clubadmin@ladg.com');
        $client->loginUser($clubAdmin);

        return $client;
    }

    protected static function createLoggedInAsCoachClient(): KernelBrowser
    {
        $client = parent::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $coach = $userRepository->findOneByEmail('coach@ladg.com');
        $client->loginUser($coach);

        return $client;
    }
}
