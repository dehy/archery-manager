<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ConsentLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConsentController extends AbstractController
{
    private const array VALID_ACTIONS = ['accepted', 'declined', 'partial'];

    #[Route('/api/consent', name: 'app_api_consent', methods: ['POST'])]
    public function store(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            /** @var array{services?: mixed, action?: mixed, policyVersion?: mixed} $data */
            $data = $request->toArray();
        } catch (\Exception) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $action = isset($data['action']) && \is_string($data['action']) ? $data['action'] : null;
        if (null === $action || !\in_array($action, self::VALID_ACTIONS, true)) {
            return new JsonResponse(
                ['error' => 'Invalid action. Must be one of: '.implode(', ', self::VALID_ACTIONS)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $services = isset($data['services']) && \is_array($data['services']) ? $data['services'] : [];
        $policyVersion = isset($data['policyVersion']) && \is_string($data['policyVersion'])
            ? $data['policyVersion']
            : '';

        /** @var User|null $user */
        $user = $this->getUser();

        $consentLog = new ConsentLog();
        $consentLog
            ->setUser($user)
            ->setServices(array_values(array_filter($services, \is_string(...))))
            ->setAction($action)
            ->setPolicyVersion($policyVersion)
            ->setIpAddressAnonymized($this->anonymizeIp($request->getClientIp() ?? ''))
            ->setUserAgent($request->headers->get('User-Agent'));

        $entityManager->persist($consentLog);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    private function anonymizeIp(string $ip): string
    {
        if ('' === $ip) {
            return '';
        }

        if (str_contains($ip, ':')) {
            // IPv6: zero the last 80 bits (10 bytes) of the 16-byte address
            $packed = inet_pton($ip);
            if (false !== $packed) {
                $anonymized = inet_ntop(substr($packed, 0, 6).str_repeat("\x00", 10));
                $result = $anonymized ?: '';
            } else {
                $result = '';
            }
        } else {
            // IPv4: zero the last octet
            $parts = explode('.', $ip);
            $result = '';
            if (4 === \count($parts)) {
                $parts[3] = '0';
                $result = implode('.', $parts);
            }
        }

        return $result;
    }
}
