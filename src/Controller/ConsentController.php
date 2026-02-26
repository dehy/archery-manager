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
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ConsentController extends AbstractController
{
    private const array VALID_ACTIONS = ['accepted', 'declined', 'partial'];

    private const int MAX_SERVICES = 20;

    private const int MAX_SERVICE_LENGTH = 64;

    private const int MAX_POLICY_VERSION_LENGTH = 32;

    private const int MAX_USER_AGENT_LENGTH = 512;

    private const string ERROR_KEY = 'error';

    public function __construct(private readonly RateLimiterFactory $consentLimiter)
    {
    }

    #[Route('/api/consent', name: 'app_api_consent', methods: ['POST'])]
    public function store(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $rateLimitError = $this->checkRateLimit($request);
        if ($rateLimitError instanceof JsonResponse) {
            return $rateLimitError;
        }

        $payloadOrError = $this->parseAndValidatePayload($request);
        if ($payloadOrError instanceof JsonResponse) {
            return $payloadOrError;
        }

        $userAgent = $request->headers->get('User-Agent');
        if (null !== $userAgent) {
            $userAgent = substr($userAgent, 0, self::MAX_USER_AGENT_LENGTH);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        $consentLog = new ConsentLog();
        $consentLog
            ->setUser($user)
            ->setServices($payloadOrError['services'])
            ->setAction($payloadOrError['action'])
            ->setPolicyVersion($payloadOrError['policyVersion'])
            ->setIpAddressAnonymized($this->anonymizeIp($request->getClientIp() ?? ''))
            ->setUserAgent($userAgent);

        $entityManager->persist($consentLog);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    private function checkRateLimit(Request $request): ?JsonResponse
    {
        $limit = $this->consentLimiter->create($request->getClientIp() ?? 'unknown')->consume(1);
        if (!$limit->isAccepted()) {
            return new JsonResponse(
                [self::ERROR_KEY => 'Too many requests. Please try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $limit->getRetryAfter()->getTimestamp() - time()],
            );
        }

        return null;
    }

    /**
     * @return array{action: string, policyVersion: string, services: list<string>}|JsonResponse
     */
    private function parseAndValidatePayload(Request $request): array|JsonResponse
    {
        try {
            /** @var array{services?: mixed, action?: mixed, policyVersion?: mixed} $data */
            $data = $request->toArray();
        } catch (\Exception) {
            return new JsonResponse([self::ERROR_KEY => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $validationError = $this->validateData($data);
        if ($validationError instanceof JsonResponse) {
            return $validationError;
        }

        $action = \is_string($data['action'] ?? null) ? $data['action'] : '';
        $policyVersion = \is_string($data['policyVersion'] ?? null) ? $data['policyVersion'] : '';
        $rawServices = \is_array($data['services'] ?? null) ? $data['services'] : [];

        return [
            'action' => $action,
            'policyVersion' => $policyVersion,
            'services' => array_values(array_filter($rawServices, \is_string(...))),
        ];
    }

    /**
     * @param array{services?: mixed, action?: mixed, policyVersion?: mixed} $data
     */
    private function validateData(array $data): ?JsonResponse
    {
        $action = isset($data['action']) && \is_string($data['action']) ? $data['action'] : null;
        if (null === $action || !\in_array($action, self::VALID_ACTIONS, true)) {
            return new JsonResponse(
                [self::ERROR_KEY => 'Invalid action. Must be one of: '.implode(', ', self::VALID_ACTIONS)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $policyVersion = isset($data['policyVersion']) && \is_string($data['policyVersion']) ? $data['policyVersion'] : '';
        $policyError = $this->validatePolicyVersion($policyVersion);
        if ($policyError instanceof JsonResponse) {
            return $policyError;
        }

        $rawServices = isset($data['services']) && \is_array($data['services']) ? $data['services'] : [];

        return $this->validateServices(array_values(array_filter($rawServices, \is_string(...))));
    }

    private function validatePolicyVersion(string $version): ?JsonResponse
    {
        if ('' === $version) {
            return new JsonResponse(
                [self::ERROR_KEY => 'policyVersion is required and must not be empty.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (\strlen($version) > self::MAX_POLICY_VERSION_LENGTH) {
            return new JsonResponse(
                [self::ERROR_KEY => \sprintf('policyVersion must not exceed %d characters.', self::MAX_POLICY_VERSION_LENGTH)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return null;
    }

    /**
     * @param list<string> $services
     */
    private function validateServices(array $services): ?JsonResponse
    {
        if (\count($services) > self::MAX_SERVICES) {
            return new JsonResponse(
                [self::ERROR_KEY => \sprintf('services must not contain more than %d items.', self::MAX_SERVICES)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        foreach ($services as $service) {
            if (\strlen($service) > self::MAX_SERVICE_LENGTH) {
                return new JsonResponse(
                    [self::ERROR_KEY => \sprintf('Each service name must not exceed %d characters.', self::MAX_SERVICE_LENGTH)],
                    Response::HTTP_BAD_REQUEST,
                );
            }
        }

        return null;
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
