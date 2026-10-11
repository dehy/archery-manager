<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class PracticeAdviceControllerTest extends LoggedInTestCase
{
    public function testTheAdvicePageRendersTheMarkdownSafely(): void
    {
        $client = self::createLoggedInAsUserClient();
        $advice = $this->advice("Gardez le **dos droit**.\n\n- Pieds écartés\n\n<script>alert(1)</script>[piège](javascript:alert(2))");

        $crawler = $client->request(Request::METHOD_GET, '/practice-advices/'.$advice->getId());

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('.mt-3')->last()->html();
        $this->assertStringContainsString('<strong>dos droit</strong>', $body);
        $this->assertStringContainsString('<li>Pieds écartés</li>', $body);
        $this->assertStringNotContainsString('<script', $body, 'At most inert, escaped text.');
        $this->assertStringNotContainsString('href="javascript:', $body);
    }

    private function advice(string $markdown): PracticeAdvice
    {
        $users = self::getContainer()->get(UserRepository::class);
        $member = $users->findOneByEmail('user1@ladg.com');
        $coach = $users->findOneByEmail('coach@ladg.com');
        $this->assertInstanceOf(User::class, $member);
        $this->assertInstanceOf(User::class, $coach);
        $licensee = $member->getLicensees()->first();
        $author = $coach->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);
        $this->assertInstanceOf(Licensee::class, $author);

        $advice = new PracticeAdvice();
        $advice->setLicensee($licensee)->setAuthor($author)->setTitle('Posture')->setAdvice($markdown);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($advice);
        $entityManager->flush();

        return $advice;
    }
}
