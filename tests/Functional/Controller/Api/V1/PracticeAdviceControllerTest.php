<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\PracticeAdviceAttachment;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

final class PracticeAdviceControllerTest extends ApiWebTestCase
{
    private const string URL = '/api/v1/practice-advices';

    public function testItRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request(Request::METHOD_GET, self::URL.'/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testTheListHoldsMyAdviceNewestFirstWithoutTheArchivedOnes(): void
    {
        $client = self::createClient();
        $me = $this->licenseeOf(self::MEMBER);
        $older = $this->advice($me, 'Ancien', '2027-01-10');
        $newer = $this->advice($me, 'Récent', '2027-03-10');
        $archived = $this->advice($me, 'Archivé', '2027-02-10', archived: true);
        $this->advice($this->licenseeOf(self::OTHER_MEMBER), 'Pour un autre', '2027-04-10');
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, self::URL, $token);

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'PracticeAdviceList');
        $this->assertSame([$newer->getId(), $older->getId()], array_column($this->json($client)['advices'], 'id'));

        $this->get($client, self::URL.'?archived=true', $token);
        $this->assertSame([$newer->getId(), $archived->getId(), $older->getId()], array_column($this->json($client)['advices'], 'id'));
    }

    public function testTheListCountsTheAttachments(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $advice = $this->advice($this->licenseeOf(self::MEMBER), 'Avec fichiers', '2027-03-10');
        $this->attach($advice, 'advice/a.pdf', 'application/pdf', 'a');
        $this->attach($advice, 'advice/b.pdf', 'application/pdf', 'b');

        $this->get($client, self::URL, $this->tokenFor(self::MEMBER));

        $this->assertSame(2, $this->json($client)['advices'][0]['attachments_count']);
    }

    public function testTheAdviceIsShownWithItsTextSanitized(): void
    {
        $client = self::createClient();
        $advice = $this->advice(
            $this->licenseeOf(self::MEMBER),
            'Posture',
            '2027-03-10',
            '<p onclick="steal()">Gardez le <strong>dos droit</strong>.</p><script>alert(1)</script><a href="javascript:alert(2)">piège</a><a href="https://example.org/video">vidéo</a><img src="http://example.org/x.png" onerror="alert(3)">',
        );

        $this->get($client, self::URL.'/'.$advice->getId(), $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'PracticeAdviceResponse');
        $body = $this->json($client)['advice'];
        $this->assertSame('Posture', $body['title']);
        $this->assertSame($advice->getAuthor()->getFirstname(), $body['author_firstname']);
        $html = $body['advice_html'];
        $this->assertStringContainsString('<strong>dos droit</strong>', (string) $html);
        $this->assertStringContainsString('https://example.org/video', (string) $html);
        $this->assertStringNotContainsString('script', (string) $html);
        $this->assertStringNotContainsString('alert', (string) $html);
        $this->assertStringNotContainsString('onclick', (string) $html);
        $this->assertStringNotContainsString('onerror', (string) $html);
        $this->assertStringNotContainsString('javascript:', (string) $html);
        $this->assertStringNotContainsString('http://', (string) $html);
    }

    public function testSomeoneElsesAdviceIsNotFound(): void
    {
        $client = self::createClient();
        $theirs = $this->advice($this->licenseeOf(self::OTHER_MEMBER), 'Pour un autre', '2027-04-10');
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, self::URL.'/'.$theirs->getId(), $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseMatchesSchema($client, 'Error');

        $this->get($client, self::URL.'/99999999', $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAnAttachmentIsListedAndStreamedToItsOwnerOnly(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $advice = $this->advice($this->licenseeOf(self::MEMBER), 'Avec fichier', '2027-03-10');
        $attachment = $this->attach($advice, 'advice/plan.pdf', 'application/pdf', '%PDF-1.4 plan');

        $this->get($client, self::URL.'/'.$advice->getId(), $this->tokenFor(self::MEMBER));
        $listed = $this->json($client)['advice']['attachments'];
        $this->assertCount(1, $listed);
        $this->assertSame(self::URL.'/'.$advice->getId().'/attachments/'.$attachment->getId(), $listed[0]['url']);
        $this->assertSame('misc', $listed[0]['type']['code']);
        $this->assertStringNotContainsString('advice/plan.pdf', (string) $client->getResponse()->getContent(), 'The storage key is not exposed.');

        $this->get($client, $listed[0]['url'], $this->tokenFor(self::MEMBER));
        $this->assertResponseIsSuccessful();
        $this->assertSame('%PDF-1.4 plan', $client->getInternalResponse()->getContent());

        $this->get($client, $listed[0]['url'], $this->tokenFor(self::OTHER_MEMBER));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAnAttachmentOfAnotherAdviceIsNotServed(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $me = $this->licenseeOf(self::MEMBER);
        $first = $this->advice($me, 'Premier', '2027-03-10');
        $second = $this->advice($me, 'Second', '2027-03-11');
        $attachment = $this->attach($first, 'advice/a.pdf', 'application/pdf', 'a');

        $this->get($client, self::URL.'/'.$second->getId().'/attachments/'.$attachment->getId(), $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function advice(Licensee $for, string $title, string $createdAt, string $text = '<p>Conseil</p>', bool $archived = false): PracticeAdvice
    {
        $advice = new PracticeAdvice();
        $advice->setLicensee($for)
            ->setAuthor($this->licenseeOf(self::COACH))
            ->setTitle($title)
            ->setAdvice($text)
            ->setCreatedAt(new \DateTimeImmutable($createdAt));
        if ($archived) {
            $advice->setArchivedAt(new \DateTimeImmutable('2027-05-01'));
        }

        $this->entityManager()->persist($advice);
        $this->entityManager()->flush();

        return $advice;
    }

    private function attach(PracticeAdvice $advice, string $key, string $mimeType, string $content): PracticeAdviceAttachment
    {
        /** @var FilesystemOperator $storage */
        $storage = self::getContainer()->get('licensees_storage');
        $storage->write($key, $content);

        $file = new EmbeddedFile();
        $file->setName($key);
        $file->setOriginalName(basename($key));
        $file->setMimeType($mimeType);
        $file->setSize(\strlen($content));

        $attachment = new PracticeAdviceAttachment();
        $attachment->setPracticeAdvice($advice)->setType('misc')->setFile($file);
        $attachment->setUpdatedAt(new \DateTimeImmutable('2027-01-15 10:00:00'));
        $this->entityManager()->persist($attachment);
        $this->entityManager()->flush();

        return $attachment;
    }
}
