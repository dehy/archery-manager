<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\TokenRefresh;
use App\Dto\TokenResult;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<TokenRefresh, TokenResult>
 */
final readonly class TokenRefreshProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('', 'User not authenticated');
        }

        // Generate new JWT token
        $token = $this->jwtManager->create($user);

        return new TokenResult($token);
    }
}
