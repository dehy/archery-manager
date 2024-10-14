<?php

declare(strict_types=1);

namespace App\Helper;

use App\DBAL\Types\LicenseeAttachmentType;
use App\DBAL\Types\UserRoleType;
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
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
        protected UserRepository $userRepository,
        protected HttpClientInterface $httpClient,
    ) {
        $this->mimeTypes = new MimeTypes();
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    public function getScrapper(Club $club): FftaScrapper
    {
        if (!isset($this->scrappers[$club->getId()])) {
            $this->scrappers[$club->getId()] = new FftaScrapper($club, $this->httpClient);
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
    public function syncLicensees(Club $club, int $season): array
    {
        $syncResults = array_fill_keys(array_map(fn ($enum) => $enum->value, SyncReturnValues::cases()), []);
        $scrapper = $this->getScrapper($club);
        $fftaIds = $scrapper->fetchLicenseeIdList($season);
        $this->logger->notice(
            \sprintf('[FFTA] Found %s licensees in %s', \count($fftaIds), $season),
        );

        foreach ($fftaIds as $fftaId) {
            $this->logger->notice(\sprintf('==== %s ====', $fftaId));

            $syncReturn = $this->syncLicenseeWithId($club, $fftaId, $season);
            $syncResults[$syncReturn->value][] = $fftaId;
        }

        if (!empty($syncResults[SyncReturnValues::CREATED->value]) || !empty($syncResults[SyncReturnValues::UPDATED->value])) {
            $managers = $this->userRepository->findByClubAndRole($club, UserRoleType::CLUB_ADMIN, $season);
            $this->emailHelper->sendLicenseesSyncResults($managers, $syncResults);
        }

        return $syncResults;
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function syncLicenseeWithId(Club $club, string $fftaId, int $season): SyncReturnValues
    {
        $syncResult = SyncReturnValues::UNTOUCHED;
        $scrapper = $this->getScrapper($club);

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class,
        );

        $licensee = $licenseeRepository->findOneByFftaId($fftaId);
        $fftaProfile = $scrapper->fetchLicenseeProfile($fftaId, $season);
        $fftaLicensee = LicenseeFactory::createFromFftaProfile($fftaProfile);
        if (!$licensee) {
            $syncResult = SyncReturnValues::CREATED;
            $this->logger->notice(
                \sprintf(
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
            if ($fftaProfilePicture instanceof LicenseeAttachment) {
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
                \sprintf(
                    '~ Merging existing Licensee: %s (%s)',
                    $licensee->__toString(),
                    $licensee->getFftaMemberCode(),
                ),
            );
            $syncResult = $licensee->mergeWith($fftaLicensee);
            // TODO check image date (with its filename) instead of downloading files and calculating checksums
            $fftaProfilePicture = $this->profilePictureAttachmentForLicensee($club, $licensee);
            $fftaProfilePictureContent = $fftaProfilePicture?->getUploadedFile()?->getContent();
            $fftaProfilePictureChecksum = $fftaProfilePicture instanceof LicenseeAttachment ? sha1((string) $fftaProfilePictureContent) : null;
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

            if ($dbProfilePicture && $fftaProfilePicture instanceof LicenseeAttachment) {
                // Licensee has already a profile picture
                if ($dbProfilePictureChecksum !== $fftaProfilePictureChecksum) {
                    $this->logger->notice('  ~ Updating profile picture.');
                    $licensee->removeAttachment($dbProfilePicture);
                    $this->entityManager->remove($dbProfilePicture);

                    $licensee->addAttachment($fftaProfilePicture);
                    $licensee->setUpdatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($fftaProfilePicture);
                    $syncResult = SyncReturnValues::UPDATED;
                } else {
                    $this->logger->notice('  = Same profile picture. Not updating.');
                }
            }

            if ($dbProfilePicture && !$fftaProfilePicture instanceof LicenseeAttachment) {
                $this->logger->notice('  - Removing profile picture');
                $licensee->removeAttachment($dbProfilePicture);
                $this->entityManager->remove($dbProfilePicture);
                $syncResult = SyncReturnValues::UPDATED;
            }

            if (!$dbProfilePicture && $fftaProfilePicture instanceof LicenseeAttachment) {
                $this->logger->notice('  + Adding profile picture');
                $licensee->addAttachment($fftaProfilePicture);
                $licensee->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($fftaProfilePicture);
                $syncResult = SyncReturnValues::UPDATED;
            }

            if (!$dbProfilePicture && !$fftaProfilePicture instanceof LicenseeAttachment) {
                $this->logger->notice('  ! No profile picture');
            }
        }

        $this->entityManager->flush();

        return SyncReturnValues::UNTOUCHED === $syncResult ? $this->syncLicenseForLicensee($club, $licensee, $season) : $syncResult;
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
        if (null !== $fftaPicture && '' !== $fftaPicture && '0' !== $fftaPicture) {
            $temporaryPPPath = tempnam(sys_get_temp_dir(), \sprintf('pp_%s_', $licensee->getFftaMemberCode()));
            if (false === $temporaryPPPath) {
                throw new \Exception('Cannot generate temporary filename');
            }

            $writtenBytes = file_put_contents($temporaryPPPath, $fftaPicture);
            if (false === $writtenBytes) {
                throw new \Exception('file not written');
            }

            $mimetype = $this->mimeTypeGuesser->guessMimeType($temporaryPPPath);
            if (null === $mimetype || '' === $mimetype || '0' === $mimetype) {
                throw new \Exception('Cannot guess mimetype for profile picture');
            }

            $extension = $this->mimeTypes->getExtension($mimetype);
            if (!$extension) {
                throw new \Exception('Cannot find a corresponding extension for mimetype '.$mimetype);
            }

            $uploadedFile = new UploadedFile(
                $temporaryPPPath,
                \sprintf('photo_identite_ffta_%s.%s', $licensee->getFftaMemberCode(), $extension),
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
    public function syncLicenseForLicensee(Club $club, Licensee $licensee, int $season): SyncReturnValues
    {
        $fftaLicense = $this->createLicenseForLicenseeAndSeason(
            $club,
            $licensee,
            $season,
        );
        $license = $licensee->getLicenseForSeason($fftaLicense->getSeason());
        if (!$license instanceof License) {
            $this->logger->notice(\sprintf('  + New License for: %s', $season));
            $license = $fftaLicense;
            $license->setLicensee($licensee);
            $this->entityManager->persist($license);
            $syncResult = SyncReturnValues::CREATED;
        } else {
            $this->logger->notice(\sprintf('  ~ Merging existing License for %s', $fftaLicense->getSeason()));
            $syncResult = $license->mergeWith($fftaLicense);
        }

        $this->entityManager->flush();

        return $syncResult;
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
