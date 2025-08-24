<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\EmailVerification;
use App\Dto\EmailVerificationResponse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<EmailVerification, EmailVerificationResponse>
 */
final readonly class EmailVerificationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof EmailVerification) {
            throw new UnprocessableEntityHttpException('Invalid data provided');
        }

        // Find user by verification token
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['emailVerificationToken' => $data->token]);

        if (!$user || !$user->isEmailVerificationTokenValid()) {
            throw new UnprocessableEntityHttpException('Invalid or expired verification token');
        }

        // Verify the user
        $user->isVerified = true;
        $user->clearEmailVerificationToken();

        $this->entityManager->flush();

        return new EmailVerificationResponse(true, 'Email verified successfully');
    }
}
