<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UserRegistration;
use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<UserRegistration, User>
 */
final readonly class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private EmailService $emailService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserRegistration) {
            throw new UnprocessableEntityHttpException('Invalid data provided');
        }

        // Validate the DTO
        $violations = $this->validator->validate($data);
        if (count($violations) > 0) {
            throw new UnprocessableEntityHttpException((string) $violations);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);
        
        if ($existingUser) {
            throw new UnprocessableEntityHttpException('A user with this email already exists');
        }

        // Create new user
        $user = new User();
        $user->email = $data->email;
        $user->givenName = $data->givenName;
        $user->familyName = $data->familyName;
        $user->gender = $data->gender;
        $user->telephone = $data->telephone;
        $user->isVerified = false; // User starts unverified

        // Generate email verification token
        $user->generateEmailVerificationToken();

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        // Persist the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send verification email
        $this->emailService->sendEmailVerification($user);
        
        return $user;
    }
}
