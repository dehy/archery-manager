<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ResetPasswordApiSessionsTest extends WebTestCase
{
    private const string EMAIL = 'clubadmin@ladg.com';

    private const string OTHER_EMAIL = 'coach@ladg.com';

    private const string PASSWORD = 'user';

    public function testResettingThePasswordSignsTheUserOutOfEveryDevice(): void
    {
        $client = self::createClient();
        $phone = $this->apiLogin($client, self::EMAIL);
        $tablet = $this->apiLogin($client, self::EMAIL);
        $someoneElse = $this->apiLogin($client, self::OTHER_EMAIL);

        $this->resetPassword($client, self::EMAIL, 'a-brand-new-password');

        foreach ([$phone, $tablet] as $tokens) {
            $client->request(Request::METHOD_GET, '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']]);
            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

            $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/refresh', ['refresh_token' => $tokens['refresh_token']]);
            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        }

        $client->request(Request::METHOD_GET, '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$someoneElse['access_token']]);
        $this->assertResponseIsSuccessful();
    }

    public function testTheNewPasswordWorksOnTheApi(): void
    {
        $client = self::createClient();
        $this->resetPassword($client, self::EMAIL, 'a-brand-new-password');

        $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'a-brand-new-password']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function apiLogin(KernelBrowser $client, string $email): array
    {
        $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function resetPassword(KernelBrowser $client, string $email, string $newPassword): void
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $user);
        $resetToken = self::getContainer()->get(ResetPasswordHelperInterface::class)->generateResetToken($user);

        // The bundle moves the token from the URL into the session, then serves the form.
        $client->request(Request::METHOD_GET, '/reset-password/reset/'.$resetToken->getToken());
        $crawler = $client->followRedirect();

        $form = $crawler->filter('form[name="change_password_form"]')->form([
            'change_password_form[plainPassword][first]' => $newPassword,
            'change_password_form[plainPassword][second]' => $newPassword,
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/');
    }
}
