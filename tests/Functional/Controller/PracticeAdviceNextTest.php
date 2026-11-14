<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\PracticeAdviceAttachment;
use App\Entity\User;
use App\Repository\PracticeAdviceRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

/**
 * Practice advice in the redesigned interface: the list, an advice and its attachments. Advice is personal: a 404
 * for anybody but the archer it was written for.
 */
final class PracticeAdviceNextTest extends LoggedInTestCase
{
    public function testTodaysInterfaceKeepsItsAdvicePageAndHasNoList(): void
    {
        $client = self::createLoggedInAsUserClient();
        $advice = $this->ownAdvice($client);

        $client->request(Request::METHOD_GET, \sprintf('/practice-advices/%d', $advice->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-prose');

        $client->request(Request::METHOD_GET, '/practice-advices');
        $this->assertResponseRedirects('/my-profile');
    }

    public function testAMemberReadsTheirAdviceAndItsAttachments(): void
    {
        $client = self::createLoggedInAsUserClient();
        // The test storage lives in memory: keep the same kernel for every request
        $client->disableReboot();
        $this->enableNewInterface($client);
        $advice = $this->ownAdvice($client);
        $attachment = $this->attach($advice, 'advice/test-notes.pdf', '%PDF-1.4 notes');

        $client->request(Request::METHOD_GET, \sprintf('/practice-advices/%d', $advice->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $advice->getTitle());
        $this->assertSelectorTextContains('main', 'Écrit par');
        // Markdown rendered by advice_html
        $this->assertSelectorExists('.am-prose h2');
        $this->assertSelectorExists(\sprintf('a[href="/practice-advices/%d/attachments/%d"]', $advice->getId(), $attachment->getId()));
        $this->assertAnySelectorTextSame('.am-agenda-title', 'test-notes.pdf');

        $client->request(Request::METHOD_GET, \sprintf('/practice-advices/%d/attachments/%d', $advice->getId(), $attachment->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    public function testTheListShowsCurrentThenArchivedAdvice(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $advice = $this->ownAdvice($client);
        $archived = new PracticeAdvice();
        $archived->setLicensee($advice->getLicensee())->setAuthor($advice->getAuthor())
            ->setTitle('Ancien conseil')->setAdvice('Texte')->setCreatedAt(new \DateTimeImmutable('-1 year'))
            ->setArchivedAt(new \DateTimeImmutable('-6 months'));
        self::getContainer()->get(EntityManagerInterface::class)->persist($archived);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->request(Request::METHOD_GET, '/practice-advices');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#am-advice-current', 'Conseils');
        $this->assertAnySelectorTextContains('.am-agenda-title', $advice->getTitle());
        $this->assertSelectorTextContains('#am-advice-archived', 'Archivés');
        $this->assertAnySelectorTextContains('.am-agenda-title', 'Ancien conseil');
    }

    public function testSomebodyElsesAdviceAndItsFilesAreNotFound(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $other = $this->otherLicensee($client);
        $advice = new PracticeAdvice();
        $advice->setLicensee($other)->setAuthor($other)->setTitle('Privé')->setAdvice('Texte')->setCreatedAt(new \DateTimeImmutable());
        self::getContainer()->get(EntityManagerInterface::class)->persist($advice);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $attachment = $this->attach($advice, 'advice/private.pdf', '%PDF-1.4 private');

        $client->request(Request::METHOD_GET, \sprintf('/practice-advices/%d', $advice->getId()));
        $this->assertResponseStatusCodeSame(404);

        $client->request(Request::METHOD_GET, \sprintf('/practice-advices/%d/attachments/%d', $advice->getId(), $attachment->getId()));
        $this->assertResponseStatusCodeSame(404);
    }

    private function ownAdvice(KernelBrowser $client): PracticeAdvice
    {
        $licensee = $this->currentUser($client)->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);
        /** @var PracticeAdviceRepository $repository */
        $repository = self::getContainer()->get(PracticeAdviceRepository::class);
        $advice = $repository->findForLicenseeNewestFirst($licensee, false)[0] ?? null;
        $this->assertInstanceOf(PracticeAdvice::class, $advice);

        return $advice;
    }

    private function otherLicensee(KernelBrowser $client): Licensee
    {
        $user = $this->currentUser($client);
        foreach (self::getContainer()->get(EntityManagerInterface::class)->getRepository(Licensee::class)->findAll() as $licensee) {
            if (!$user->getLicensees()->contains($licensee)) {
                return $licensee;
            }
        }

        $this->fail('No other licensee');
    }

    private function attach(PracticeAdvice $advice, string $key, string $content): PracticeAdviceAttachment
    {
        /** @var FilesystemOperator $storage */
        $storage = self::getContainer()->get('licensees_storage');
        $storage->write($key, $content);

        $file = new EmbeddedFile();
        $file->setName($key);
        $file->setOriginalName(basename($key));
        $file->setMimeType('application/pdf');
        $file->setSize(\strlen($content));

        $attachment = new PracticeAdviceAttachment();
        $attachment->setPracticeAdvice($advice)->setType('misc')->setFile($file);
        $attachment->setUpdatedAt(new \DateTimeImmutable());
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($attachment);
        $entityManager->flush();

        return $attachment;
    }

    private function enableNewInterface(KernelBrowser $client): void
    {
        $this->currentUser($client)->setNewInterface(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function currentUser(KernelBrowser $client): User
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');

        $user = self::getContainer()->get(EntityManagerInterface::class)->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
