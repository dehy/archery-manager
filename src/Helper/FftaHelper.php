<?php

namespace App\Helper;

use App\DBAL\Types\LicenseeAttachmentType;
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

    public function __construct(
        protected FftaScrapper $scrapper,
        protected EntityManagerInterface $entityManager,
        protected MailerInterface $mailer,
        protected LoggerInterface $logger,
        protected FilesystemOperator $licenseesStorage,
        protected MimeTypeGuesserInterface $mimeTypeGuesser,
        protected LicenseeHelper $licenseeHelper,
    ) {
        $this->mimeTypes = new MimeTypes();
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
    public function syncLicensees(int $season): void
    {
        $fftaIds = $this->scrapper->fetchLicenseeIdList($season);
        $this->logger->info(
            sprintf('[FFTA] Found %s licensees in %s', \count($fftaIds), $season),
        );

        foreach ($fftaIds as $fftaId) {
            $this->logger->info(sprintf('==== %s ====', $fftaId));

            $this->syncLicenseeWithId($fftaId, $season);
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function syncLicenseeWithId(string $fftaId, int $season = null): Licensee
    {
        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class,
        );

        $licensee = $licenseeRepository->findOneByFftaId($fftaId);
        $fftaProfile = $this->scrapper->fetchLicenseeProfile($fftaId);
        $fftaLicensee = LicenseeFactory::createFromFftaProfile($fftaProfile);
        if (!$licensee) {
            $this->logger->info(
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

            $fftaProfilePicture = $this->profilePictureAttachmentForLicensee($licensee);
            if ($fftaProfilePicture) {
                $this->logger->info('  + Adding profile picture');
                $licensee->addAttachment($fftaProfilePicture);
                $this->entityManager->persist($fftaProfilePicture);
            } else {
                $this->logger->info('  ! No profile picture');
            }

            $this->entityManager->beginTransaction();
            $this->entityManager->persist($licensee);

            try {
                $this->licenseeHelper->sendWelcomeEmail($licensee);
            } catch (TransportExceptionInterface $exception) {
                $this->entityManager->rollback();

                throw $exception;
            }
            $this->entityManager->commit();
        } else {
            $this->logger->info(
                sprintf(
                    '~ Merging existing Licensee: %s (%s)',
                    $licensee->__toString(),
                    $licensee->getFftaMemberCode(),
                ),
            );
            $licensee->mergeWith($fftaLicensee);
            // TODO check image date (with its filename) instead of downloading files and calculating checksums
            $fftaProfilePicture = $this->profilePictureAttachmentForLicensee($licensee);
            $fftaProfilePictureContent = $fftaProfilePicture?->getUploadedFile()?->getContent();
            $fftaProfilePictureChecksum = $fftaProfilePicture ? sha1($fftaProfilePictureContent) : null;
            $dbProfilePicture = $licensee->getProfilePicture();

            try {
                $dbProfilePictureChecksum = $dbProfilePicture ?
                    $this->licenseesStorage->checksum(
                        $dbProfilePicture->getFile()->getName(),
                        ['checksum_algo' => 'sha1']
                    ) : null;
            } catch (UnableToProvideChecksum | NoSuchKeyException) {
                $dbProfilePicture = null;
                $dbProfilePictureChecksum = null;
            }

            if ($dbProfilePicture && $fftaProfilePicture) {
                // Licensee has already a profile picture
                if ($dbProfilePictureChecksum !== $fftaProfilePictureChecksum) {
                    $this->logger->info('  ~ Updating profile picture.');
                    $licensee->removeAttachment($dbProfilePicture);
                    $this->entityManager->remove($dbProfilePicture);

                    $licensee->addAttachment($fftaProfilePicture);
                    $licensee->setUpdatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($fftaProfilePicture);
                } else {
                    $this->logger->info('  = Same profile picture. Not updating.');
                }
            }
            if ($dbProfilePicture && !$fftaProfilePicture) {
                $this->logger->info('  - Removing profile picture');
                $licensee->removeAttachment($dbProfilePicture);
                $this->entityManager->remove($dbProfilePicture);
            }
            if (!$dbProfilePicture && $fftaProfilePicture) {
                $this->logger->info('  + Adding profile picture');
                $licensee->addAttachment($fftaProfilePicture);
                $licensee->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($fftaProfilePicture);
            }
            if (!$dbProfilePicture && !$fftaProfilePicture) {
                $this->logger->info('  ! No profile picture');
            }
        }
        $this->entityManager->flush();

        $this->syncLicensesForLicensee($licensee, $season);

        return $licensee;
    }

    public function fetchProfilePictureForLicensee(Licensee $licensee): ?string
    {
        return $this->scrapper->fetchLicenseeProfilePicture($licensee->getFftaId());
    }

    /**
     * @throws \Exception
     */
    public function profilePictureAttachmentForLicensee(Licensee $licensee): ?LicenseeAttachment
    {
        $fftaPicture = $this->fetchProfilePictureForLicensee($licensee);
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
     * @return License[]
     * @throws \Exception
     */
    public function syncLicensesForLicensee(Licensee $licensee, ?int $season = null): array
    {
        $fftaLicenses = $this->createLicensesForLicenseeAndSeason(
            $licensee,
            $season,
        );
        $licenses = [];
        foreach ($fftaLicenses as $fftaLicense) {
            if ($season !== null && $fftaLicense->getSeason() !== $season) {
                continue;
            }
            $license = $licensee->getLicenseForSeason($fftaLicense->getSeason());
            if (!$license) {
                $this->logger->info(sprintf('  + New License for: %s', $season));
                $license = $fftaLicense;
                $license->setLicensee($licensee);
                $this->entityManager->persist($license);
            } else {
                $this->logger->info(sprintf('  ~ Merging existing License for %s', $fftaLicense->getSeason()));
                $license->mergeWith($fftaLicense);
            }
            $licenses[] = $license;
        }

        $this->entityManager->flush();

        return $licenses;
    }

    /**
     * @return License[]
     * @throws \Exception
     */
    public function createLicensesForLicenseeAndSeason(
        ?Licensee $licensee,
        ?int $seasonYear = null,
    ): array {
        return $this->scrapper->fetchLicenseeLicenses(
            $licensee->getFftaId(),
            $seasonYear,
        );
    }
}
