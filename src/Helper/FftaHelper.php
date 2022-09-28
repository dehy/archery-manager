<?php

namespace App\Helper;

use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Factory\LicenseeFactory;
use App\Factory\UserFactory;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Scrapper\FftaScrapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class FftaHelper
{
    public function __construct(
        protected FftaScrapper $scrapper,
        protected EntityManagerInterface $entityManager,
        protected MailerInterface $mailer,
        protected LoggerInterface $logger,
        protected FilesystemOperator $profilePicturesStorage
    ) {
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function syncLicensees(int $season): void
    {
        $fftaIds = $this->scrapper->fetchLicenseeIdList($season);
        $this->logger->info(
            sprintf('[FFTA] Found %s licensees in %s', count($fftaIds), $season),
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
     * @throws FilesystemException
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

            $fftaPicture = $this->fetchProfilePictureForLicensee($licensee);
            if ($fftaPicture) {
                $this->profilePicturesStorage->write(sprintf('%s.jpg', $licensee->getFftaMemberCode()), $fftaPicture);
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
                ])
            ;

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
            $fftaPicture = $this->fetchProfilePictureForLicensee($licensee);

            try {
                $exitingPicture = $this->profilePicturesStorage->read(sprintf('%s.jpg', $licensee->getFftaMemberCode()));
            } catch (UnableToReadFile) {
                $exitingPicture = null;
            }

            if ($fftaPicture) {
                if (($exitingPicture && sha1($exitingPicture) !== sha1($fftaPicture)) || !$exitingPicture) {
                    $this->profilePicturesStorage->write(sprintf('%s.jpg', $licensee->getFftaMemberCode()), $fftaPicture);
                    $licensee->setUpdatedAt(new DateTimeImmutable());
                }
            } else {
                if ($exitingPicture) {
                    $this->profilePicturesStorage->delete(sprintf('%s.jpg', $licensee->getFftaMemberCode()));
                    $licensee->setUpdatedAt(new DateTimeImmutable());
                }
            }
        }
        $this->entityManager->flush();

        return $licensee;
    }

    public function fetchProfilePictureForLicensee(Licensee $licensee): ?string
    {
        return $this->scrapper->fetchLicenseeProfilePicture($licensee->getFftaId());
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
     * @throws Exception
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
