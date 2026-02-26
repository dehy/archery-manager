<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\ConsentLogRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConsentControllerAuthenticatedTest extends LoggedInTestCase
{
    private const string URL_CONSENT = '/api/consent';

    public function testAuthenticatedConsentPostReturnsCreated(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testConsentLogUserIsSetForAuthenticated(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var ConsentLogRepository $repo */
        $repo = self::getContainer()->get(ConsentLogRepository::class);
        $logs = $repo->findBy([], ['id' => 'DESC'], 1);

        $this->assertCount(1, $logs);
        $this->assertInstanceOf(User::class, $logs[0]->getUser());
    }
}
