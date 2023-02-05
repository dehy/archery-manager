<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\DiscordClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process.
     */
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
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
        ClientRegistry $clientRegistry
    ): RedirectResponse {
        if ($request->query->get('error')) {
            $description = $request->query->get('error_description');
            $this->addFlash('warning', 'La connexion à Discord a été refusée : '.$description);
            // Todo add sentry exception

            return $this->redirectToRoute('app_user_account');
        }

        /** @var DiscordClient $client */
        $client = $clientRegistry->getClient('discord');

        try {
            /** @var DiscordResourceOwner $discordUser */
            $discordUser = $client->fetchUser();

            /** @var User $user */
            $user = $this->getUser();
            $user->setDiscordId($discordUser->getId());
            $user->setDiscordAccessToken($client->getAccessToken());
            $entityManager->flush();

            $this->addFlash('success', 'Association à Discord réussie !');

            return $this->redirectToRoute('app_user_account');
        } catch (IdentityProviderException $e) {
            $this->addFlash('danger', 'Une erreur est survenue durant la connexion à Discord : '.$e->getMessage());
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
        $entityManager->flush();

        return $this->redirectToRoute('app_user_account');
    }
}
