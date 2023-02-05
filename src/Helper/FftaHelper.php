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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToProvideChecksum;
use Mimey\MimeTypes;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
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

            $licensee = $this->syncLicenseeWithId($fftaId);

            $this->syncLicenseForLicensee($licensee, $season);
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     */
    public function syncLicenseeWithId(string $fftaId): Licensee
    {
        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class,
        );

        $licensee = $licenseeRepository->findOneByFftaId($fftaId);
        $fftaLicensee = $this->createLicenseeFromFftaId($fftaId);
        if (!$licensee) {
            $this->logger->info(
                sprintf(
                    '+ New Licensee: %s (%s)',
                    $fftaLicensee->__toString(),
                    $fftaLicensee->getFftaMemberCode(),
                ),
            );
            $licensee = $fftaLicensee;

            $fftaProfilePicture = $this->profilePictureAttachmentForLicensee($licensee);
            if ($fftaProfilePicture) {
                $this->logger->info('  + Adding profile picture');
                $licensee->addAttachment($fftaProfilePicture);
                $this->entityManager->persist($fftaProfilePicture);
            }

            $this->entityManager->beginTransaction();
            $this->entityManager->persist($licensee);

            $email = (new TemplatedEmail())
                ->to($licensee->getUser()->getEmail())
                ->replyTo('lesarchersdeguyenne@gmail.com')
                ->subject('Bienvenue aux Archers de Guyenne')
                ->htmlTemplate(
                    'licensee/mail_account_created.html.twig',
                )
                ->context([
                    'licensee' => $licensee,
                ]);

            try {
                $this->mailer->send($email);
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
            } catch (UnableToProvideChecksum) {
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

        return $licensee;
    }

    public function fetchProfilePictureForLicensee(Licensee $licensee): ?string
    {
        return $this->scrapper->fetchLicenseeProfilePicture($licensee->getFftaId());
    }

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
     */
    public function syncLicenseForLicensee(Licensee $licensee, int $season): License
    {
        $license = $licensee->getLicenseForSeason($season);
        $fftaLicense = $this->createLicenseForLicenseeAndSeason(
            $licensee,
            $season,
        );
        if (!$license) {
            $this->logger->info(sprintf('  + New License for: %s', $season));
            $license = $fftaLicense;
            $license->setLicensee($licensee);
            $this->entityManager->persist($license);
        } else {
            $this->logger->info(sprintf('  ~ Merging existing License for: %s', $season));
            $license->mergeWith($fftaLicense);
        }
        $this->entityManager->flush();

        return $license;
    }

    /**
     * Creates a Licensee entity from the FFTA licensee profile.
     *
     * @throws NonUniqueResultException
     */
    public function createLicenseeFromFftaId(int $fftaId): Licensee
    {
        $fftaProfile = $this->scrapper->fetchLicenseeProfile($fftaId);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneByEmail($fftaProfile->getEmail());

        if (!$user) {
            $user = UserFactory::createFromFftaProfile($fftaProfile);
            $this->entityManager->persist($user);
        }

        $licensee = LicenseeFactory::createFromFftaProfile($fftaProfile);
        $licensee->setUser($user);

        return $licensee;
    }

    /**
     * @throws \Exception
     */
    public function createLicenseForLicenseeAndSeason(
        ?Licensee $licensee,
        int $seasonYear,
    ): License {
        $licenses = $this->scrapper->fetchLicenseeLicenses(
            $licensee->getFftaId(),
            $seasonYear,
        );

        return $licenses[$seasonYear];
    }
}
