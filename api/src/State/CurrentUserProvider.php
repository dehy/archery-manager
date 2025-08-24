<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\CurrentUser;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProviderInterface<CurrentUser>
 */
final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?CurrentUser
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('', 'User not authenticated');
        }

        $currentUser = new CurrentUser();
        $currentUser->id = $user->getId();
        $currentUser->email = $user->email;
        $currentUser->givenName = $user->givenName;
        $currentUser->familyName = $user->familyName;
        $currentUser->gender = $user->gender?->value;
        $currentUser->telephone = $user->telephone;
        $currentUser->roles = $user->getRoles();
        $currentUser->isVerified = $user->isVerified;
        
        // Map licensees
        $currentUser->licensees = array_map(fn($licensee) => [
            'id' => $licensee->getId(),
            'fftaMemberCode' => $licensee->fftaMemberCode,
            // Add other licensee fields as needed
        ], $user->licensees->toArray());

        return $currentUser;
    }
}
