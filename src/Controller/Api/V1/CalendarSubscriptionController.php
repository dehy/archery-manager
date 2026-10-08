<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Entity\Licensee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The personal iCal feed of the selected licensee (the events they registered for), to subscribe to from a
 * calendar app. The URL is the credential, as on the web: whoever holds it reads the feed, so it is
 * revocable, and generating a new one invalidates the previous one.
 */
final readonly class CalendarSubscriptionController
{
    private const string PATH = '/api/v1/calendar-subscription';

    public function __construct(
        private MemberContext $context,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(self::PATH, name: 'api_v1_calendar_subscription', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return $this->respond($this->licensee());
    }

    #[Route(self::PATH, name: 'api_v1_calendar_subscription_generate', methods: ['PUT'])]
    public function generate(): JsonResponse
    {
        $licensee = $this->licensee()->generateCalendarToken();
        $this->entityManager->flush();

        return $this->respond($licensee);
    }

    #[Route(self::PATH, name: 'api_v1_calendar_subscription_revoke', methods: ['DELETE'])]
    public function revoke(): Response
    {
        $this->licensee()->revokeCalendarToken();
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function licensee(): Licensee
    {
        return $this->context->licensee() ?? throw new AccessDeniedHttpException('No licensee selected.');
    }

    private function respond(Licensee $licensee): JsonResponse
    {
        $token = $licensee->getCalendarToken();

        return PrivateJson::response(['subscription' => [
            'url' => null === $token ? null : $this->urlGenerator->generate('app_calendar_feed', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
        ]]);
    }
}
