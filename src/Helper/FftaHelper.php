<?php

namespace App\Helper;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Club;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Entity\User;
use App\Factory\LicenseeFactory;
use App\Factory\UserFactory;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Scrapper\FftaScrapper;
use AsyncAws\S3\Exception\NoSuchKeyException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToProvideChecksum;
use Mimey\MimeTypes;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

class FftaHelper
{
    protected MimeTypes $mimeTypes;

    protected array $scrappers = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected MailerInterface $mailer,
        protected LoggerInterface $logger,
        protected FilesystemOperator $licenseesStorage,
        protected MimeTypeGuesserInterface $mimeTypeGuesser,
        protected EmailHelper $emailHelper,
    ) {
        $this->mimeTypes = new MimeTypes();
    }

    public function getScrapper(Club $club): FftaScrapper
    {
        if (!isset($this->scrappers[$club->getId()])) {
            $this->scrappers[$club->getId()] = new FftaScrapper($club);
        }

        return $this->scrappers[$club->getId()];
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function syncLicensees(Club $club, int $season): void
    {
        $scrapper = $this->getScrapper($club);
        $fftaIds = $scrapper->fetchLicenseeIdList($season);
        $this->logger->notice(
            sprintf('[FFTA] Found %s licensees in %s', \count($fftaIds), $season),
        );

        foreach ($fftaIds as $fftaId) {
            $this->logger->notice(sprintf('==== %s ====', $fftaId));

            $this->syncLicenseeWithId($club, $fftaId, $season);
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function syncLicenseeWithId(Club $club, string $fftaId, int $season): Licensee
    {
        $scrapper = $this->getScrapper($club);

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class,
        );

        $licensee = $licenseeRepository->findOneByFftaId($fftaId);
        $fftaProfile = $scrapper->fetchLicenseeProfile($fftaId, $season);
        $fftaLicensee = LicenseeFactory::createFromFftaProfile($fftaProfile);
        if (!$licensee) {
            $this->logger->notice(
                sprintf(
                    '+ New Licensee: %s (%s)',
                    $fftaLicensee->__toString(),
                    $fftaLicensee->getFftaMemberCode(),
                ),
            );
            $licensee = $fftaLicensee;

            /** @var UserRepository $userRepository */
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneByEmail($fftaProfile->getEmail());

            if (!$user) {
                $user = UserFactory::createFromFftaProfile($fftaProfile);
                $this->entityManager->persist($user);
            }
            $licensee->setUser($user);

            $fftaProfilePicture = $this->profilePictureAttachmentForLicensee($club, $licensee);
            if ($fftaProfilePicture) {
                $this->logger->notice('  + Adding profile picture');
                $licensee->addAttachment($fftaProfilePicture);
                $this->entityManager->persist($fftaProfilePicture);
            } else {
                $this->logger->notice('  ! No profile picture');
            }

            $this->entityManager->beginTransaction();
            $this->entityManager->persist($licensee);

            try {
                $this->emailHelper->sendWelcomeEmail($licensee, $club);
            } catch (TransportExceptionInterface $exception) {
                $this->entityManager->rollback();

                throw $exception;
            }
            $this->entityManager->commit();
        } else {
            $this->logger->notice(
                sprintf(
                    '~ Merging existing Licensee: %s (%s)',
                    $licensee->__toString(),
                    $licensee->getFftaMemberCode(),
                ),
            );
            $licensee->mergeWith($fftaLicensee);
            // TODO check image date (with its filename) instead of downloading files and calculating checksums
            $fftaProfilePicture = $this->profilePictureAttachmentForLicensee($club, $licensee);
            $fftaProfilePictureContent = $fftaProfilePicture?->getUploadedFile()?->getContent();
            $fftaProfilePictureChecksum = $fftaProfilePicture ? sha1($fftaProfilePictureContent) : null;
            $dbProfilePicture = $licensee->getProfilePicture();

            try {
                $dbProfilePictureChecksum = $dbProfilePicture ?
                    $this->licenseesStorage->checksum(
                        $dbProfilePicture->getFile()->getName(),
                        ['checksum_algo' => 'sha1']
                    ) : null;
            } catch (UnableToProvideChecksum|NoSuchKeyException) {
                $dbProfilePicture = null;
                $dbProfilePictureChecksum = null;
            }

            if ($dbProfilePicture && $fftaProfilePicture) {
                // Licensee has already a profile picture
                if ($dbProfilePictureChecksum !== $fftaProfilePictureChecksum) {
                    $this->logger->notice('  ~ Updating profile picture.');
                    $licensee->removeAttachment($dbProfilePicture);
                    $this->entityManager->remove($dbProfilePicture);

                    $licensee->addAttachment($fftaProfilePicture);
                    $licensee->setUpdatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($fftaProfilePicture);
                } else {
                    $this->logger->notice('  = Same profile picture. Not updating.');
                }
            }
            if ($dbProfilePicture && !$fftaProfilePicture) {
                $this->logger->notice('  - Removing profile picture');
                $licensee->removeAttachment($dbProfilePicture);
                $this->entityManager->remove($dbProfilePicture);
            }
            if (!$dbProfilePicture && $fftaProfilePicture) {
                $this->logger->notice('  + Adding profile picture');
                $licensee->addAttachment($fftaProfilePicture);
                $licensee->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($fftaProfilePicture);
            }
            if (!$dbProfilePicture && !$fftaProfilePicture) {
                $this->logger->notice('  ! No profile picture');
            }
        }
        $this->entityManager->flush();

        $this->syncLicenseForLicensee($club, $licensee, $season);

        return $licensee;
    }

    public function fetchProfilePictureForLicensee(Club $club, Licensee $licensee): ?string
    {
        $scrapper = $this->getScrapper($club);

        return $scrapper->fetchLicenseeProfilePicture($licensee->getFftaId());
    }

    /**
     * @throws \Exception
     */
    public function profilePictureAttachmentForLicensee(Club $club, Licensee $licensee): ?LicenseeAttachment
    {
        $fftaPicture = $this->fetchProfilePictureForLicensee($club, $licensee);
        if ($fftaPicture) {
            $temporaryPPPath = tempnam(sys_get_temp_dir(), sprintf('pp_%s_', $licensee->getFftaMemberCode()));
            if (false === $temporaryPPPath) {
                throw new \Exception('Cannot generate temporary filename');
            }
            $writtenBytes = file_put_contents($temporaryPPPath, $fftaPicture);
            if (false === $writtenBytes) {
                throw new \Exception('file not written');
            }
            $mimetype = $this->mimeTypeGuesser->guessMimeType($temporaryPPPath);
            if (!$mimetype) {
                throw new \Exception('Cannot guess mimetype for profile picture');
            }
            $extension = $this->mimeTypes->getExtension($mimetype);
            if (!$extension) {
                throw new \Exception('Cannot find a corresponding extension for mimetype '.$mimetype);
            }
            $uploadedFile = new UploadedFile(
                $temporaryPPPath,
                sprintf('photo_identite_ffta_%s.%s', $licensee->getFftaMemberCode(), $extension),
                $mimetype,
            );
            $profilePicture = new LicenseeAttachment();
            $profilePicture
                ->setType(LicenseeAttachmentType::PROFILE_PICTURE)
                ->setUploadedFile($uploadedFile);

            return $profilePicture;
        }

        return null;
    }

    /**
     * Fetch license information from the FFTA website and returns a License Entity
     * Creates a new License if none exists for the Licensee and season or merge with the
     * existing one.
     *
     * @throws \Exception
     */
    public function syncLicenseForLicensee(Club $club, Licensee $licensee, int $season): License
    {
        $fftaLicense = $this->createLicenseForLicenseeAndSeason(
            $club,
            $licensee,
            $season,
        );
        $license = $licensee->getLicenseForSeason($fftaLicense->getSeason());
        if (!$license) {
            $this->logger->notice(sprintf('  + New License for: %s', $season));
            $license = $fftaLicense;
            $license->setLicensee($licensee);
            $this->entityManager->persist($license);
        } else {
            $this->logger->notice(sprintf('  ~ Merging existing License for %s', $fftaLicense->getSeason()));
            $license->mergeWith($fftaLicense);
        }

        $this->entityManager->flush();

        return $license;
    }

    /**
     * @throws \Exception
     */
    public function createLicenseForLicenseeAndSeason(
        Club $club,
        Licensee $licensee,
        int $seasonYear,
    ): License {
        $scrapper = $this->getScrapper($club);

        return $scrapper->fetchLicenseeLicense(
            $licensee->getFftaId(),
            $seasonYear,
        );
    }
}
