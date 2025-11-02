<?php

declare(strict_types=1);

namespace App\Tests\Integration\Helper;

use App\Entity\Club;
use App\Entity\User;
use App\Helper\FftaHelper;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FftaHelperTest extends KernelTestCase
{
    use FftaHelperTestDataLoader;

    public function testSyncLicenseesSendsSummaryEmail(): void
    {
        $clubManagers = [
            (new User())
                ->setEmail('manager@club.fr')
                ->setFirstname('Firstname')
                ->setLastname('Lastname'),
        ];

        $mockUserRepository = $this->createMock(UserRepository::class);
        $mockUserRepository
            ->method('findByClubAndRole')
            ->willReturn($clubManagers);

        $baseUrl = 'https://dirigeant.ffta.fr';
        $mockResponses = function ($method, $url, $options): MockResponse {
            $url = preg_replace('!^https://dirigeant\.ffta\.fr!', '', $url);
            $url = preg_replace('!\?.*$!', '', $url);
            if ('/auth/login' === $url) {
                if ('GET' === $method) {
                    return new MockResponse(
                        ' <form id="form-login" method="post">
                                <input name="username"/>
                                <input name="password"/>
                                </form>'
                    );
                }

                if ('POST' === $method) {
                    return new JsonMockResponse();
                }
            }

            if (1 === preg_match('!/structures/fiche/\d+/licencies/ajax!', $url)) {
                $count = 5;

                return new JsonMockResponse([
                    'draw' => 1,
                    'data' => $this->getLicensees($count),
                    'total' => $count,
                ]);
            }

            if (1 === preg_match('!/personnes/fiche/\d+/infos!', $url)) {
                return new MockResponse(
                    '<img src="/elicence-core/images/default/default_profil_homme.png" alt="Photo de M John Doe" class="border-white rounded-circle" width="65" height="65">'
                );
            }

            if (1 === preg_match('!/elicence-core/images/default/default_profil_homme.png!', $url)) {
                return new MockResponse(
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAC4jAAAuIwF4pT92AAAADElEQVQImWP4z8AAAAMBAQCc479ZAAAAAElFTkSuQmCC'),
                    [
                        'response_headers' => [
                            'Content-Type' => 'image/png',
                        ],
                    ]
                );
            }

            throw new \Exception(\sprintf('Missing url handling: %s %s', $method, $url));
        };

        self::bootKernel();

        // Create and persist the club entity first with all required fields
        $club = (new Club())
            ->setName('Test Club')
            ->setCity('Test City')
            ->setPrimaryColor('#FF0000')
            ->setFftaCode('1033093')
            ->setFftaUsername('invalid@ffta.fr')
            ->setFftaPassword('invalid')
            ->setContactEmail('reply@club.invalid');

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($club);
        $entityManager->flush();

        $mockHttpClient = new MockHttpClient($mockResponses, $baseUrl);
        /** @var FftaHelper $fftaHelper */
        $fftaHelper = $this->getContainer()->get(FftaHelper::class);
        $fftaHelper->setHttpClient($mockHttpClient);
        $fftaHelper->setUserRepository($mockUserRepository);

        $fftaHelper->syncLicensees($club, 2025);

        // 5 mails queues for users (1 welcome for each), 1 summary for club admins
        $this->assertQueuedEmailCount(6);
    }
}
