<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\DiscordClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process.
     */
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connect(ClientRegistry $clientRegistry, RequestStack $requestStack): RedirectResponse
    {
        return $clientRegistry
            ->getClient('discord') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'identify', // the scopes you want to access
            ], []);
    }

    /**
     * After going to Discord, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml.
     */
    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheck(
        Request $request,
        EntityManagerInterface $entityManager,
        ClientRegistry $clientRegistry,
        RequestStack $requestStack,
    ): RedirectResponse {
        if ($request->query->get('error')) {
            $description = $request->query->get('error_description');
            $this->addFlash('warning', 'La connexion à Discord a été refusée : '.$description);
            // TODO add sentry exception

            return $this->redirectToRoute('app_user_account');
        }

        /** @var DiscordClient $client */
        $client = $clientRegistry->getClient('discord');

        try {
            $accessToken = $client->getAccessToken();
            /** @var DiscordResourceOwner $discordUser */
            $discordUser = $client->fetchUserFromToken($accessToken);

            /** @var User $user */
            $user = $this->getUser();
            $user->setDiscordId($discordUser->getId());
            $user->setDiscordAccessToken(json_encode($accessToken->jsonSerialize(), \JSON_THROW_ON_ERROR));
            $entityManager->flush();

            $this->addFlash('success', 'Association à Discord réussie !');

            return $this->redirectToRoute('app_user_account');
        } catch (IdentityProviderException $identityProviderException) {
            $this->addFlash('danger', 'Une erreur est survenue durant la connexion à Discord : '.$identityProviderException->getMessage());
            // Todo add sentry exception

            return $this->redirectToRoute('app_user_account');
        }
    }

    #[Route('/connect/discord/logout', name: 'connect_discord_logout')]
    public function logout(EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setDiscordId(null);
        $user->setDiscordAccessToken(null);

        $entityManager->flush();

        return $this->redirectToRoute('app_user_account');
    }
}
