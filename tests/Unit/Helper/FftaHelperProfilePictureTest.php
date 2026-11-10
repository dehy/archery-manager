<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Helper\EmailHelper;
use App\Helper\FftaHelper;
use App\Helper\SyncReturnValues;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypesInterface;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

/**
 * How the FFTA sync treats the picture a licensee already has.
 */
final class FftaHelperProfilePictureTest extends TestCase
{
    private Filesystem $storage;

    /** What the FFTA "returns" for the licensee's picture, and how many times it was asked. */
    private ?LicenseeAttachment $fftaPicture = null;

    private int $fetches = 0;

    private FftaHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
        $test = $this;

        $this->helper = new class($this->createStub(EntityManagerInterface::class), $this->createStub(MailerInterface::class), new NullLogger(), $this->storage, $this->createStub(MimeTypeGuesserInterface::class), $this->createStub(MimeTypesInterface::class), $this->createStub(EmailHelper::class), $this->createStub(UserRepository::class), new MockHttpClient(), $test) extends FftaHelper {
            public function __construct(EntityManagerInterface $a, MailerInterface $b, NullLogger $c, Filesystem $d, MimeTypeGuesserInterface $e, MimeTypesInterface $f, EmailHelper $g, UserRepository $h, MockHttpClient $i, private readonly FftaHelperProfilePictureTest $test)
            {
                parent::__construct($a, $b, $c, $d, $e, $f, $g, $h, $i);
            }

            #[\Override]
            public function profilePictureAttachmentForLicensee(Club $club, Licensee $licensee): ?LicenseeAttachment
            {
                return $this->test->fetchFftaPicture();
            }
        };
    }

    public function fetchFftaPicture(): ?LicenseeAttachment
    {
        ++$this->fetches;

        return $this->fftaPicture;
    }

    public function testAManualPictureIsKeptAndTheFftaIsNotAsked(): void
    {
        $licensee = new Licensee();
        $manual = $this->storedPicture($licensee, 'manual.png', 'my own picture', true);
        $this->fftaPicture = $this->fftaPictureWith('the FFTA picture');

        $result = $this->sync($licensee);

        $this->assertSame(SyncReturnValues::UNTOUCHED, $result);
        $this->assertSame(0, $this->fetches);
        $this->assertSame($manual, $licensee->getProfilePicture());
    }

    public function testAnFftaPictureIsStillReplacedWhenTheFftaOneChanges(): void
    {
        $licensee = new Licensee();
        $this->storedPicture($licensee, 'ffta.png', 'old FFTA picture', false);
        $this->fftaPicture = $this->fftaPictureWith('new FFTA picture');

        $result = $this->sync($licensee);

        $this->assertSame(SyncReturnValues::UPDATED, $result);
        $this->assertSame(1, $this->fetches);
        $this->assertSame($this->fftaPicture, $licensee->getProfilePicture());
    }

    public function testTheFftaPictureIsAddedWhenThereIsNone(): void
    {
        $licensee = new Licensee();
        $this->fftaPicture = $this->fftaPictureWith('the FFTA picture');

        $result = $this->sync($licensee);

        $this->assertSame(SyncReturnValues::UPDATED, $result);
        $this->assertSame($this->fftaPicture, $licensee->getProfilePicture());
    }

    private function sync(Licensee $licensee): SyncReturnValues
    {
        $method = new \ReflectionMethod(FftaHelper::class, 'syncProfilePicture');
        $result = $method->invoke($this->helper, new Club(), $licensee, SyncReturnValues::UNTOUCHED);
        $this->assertInstanceOf(SyncReturnValues::class, $result);

        return $result;
    }

    private function storedPicture(Licensee $licensee, string $key, string $content, bool $manual): LicenseeAttachment
    {
        $this->storage->write($key, $content);
        $file = new EmbeddedFile();
        $file->setName($key);

        $picture = new LicenseeAttachment();
        $picture->setFile($file);
        $picture->setType(LicenseeAttachmentType::PROFILE_PICTURE);
        $picture->setManualUpload($manual);

        $licensee->addAttachment($picture);

        return $picture;
    }

    private function fftaPictureWith(string $content): LicenseeAttachment
    {
        $path = tempnam(sys_get_temp_dir(), 'ffta_picture_');
        file_put_contents($path, $content);

        $picture = new LicenseeAttachment();
        $picture->setType(LicenseeAttachmentType::PROFILE_PICTURE);
        $picture->setUploadedFile(new UploadedFile($path, 'ffta.png', 'image/png', null, true));

        return $picture;
    }
}
