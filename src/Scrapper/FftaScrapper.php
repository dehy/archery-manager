<?php

namespace App\Scrapper;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\ContestEvent;
use App\Entity\License;
use App\Entity\Result;
use Doctrine\Common\Collections\ArrayCollection;
use Goutte\Client;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FftaScrapper
{
    protected Client $fftaGoalClient;
    protected bool $fftaGoalIsConnected = false;

    protected Client $fftaExtranetClient;
    protected bool $fftaExtranetIsConnected = false;
    private string $goalBaseUrl = 'https://ffta-goal.multimediabs.com';
    private string $extranetBaseUrl = 'https://extranet.ffta.fr';

    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {
        if (!$this->username || !$this->password) {
            throw new \Exception('FFTA Credentials not set');
        }
    }

    /**
     * @return int[]
     *
     * @throws \JsonException
     * @throws \JsonException
     */
    public function fetchLicenseeIdList(int $season): array
    {
        $this->loginFftaGoal();

        $url = sprintf(
            '%s/licences/afficherlistelicencies?editionAttestation=&idSaison=%s&actifs=false',
            $this->goalBaseUrl,
            $season,
        );
        $this->fftaGoalClient->xmlHttpRequest(
            'GET',
            $url,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json, text/javascript, */*; q=0.01',
            ],
        );

        $licensesData = json_decode(
            (string) $this->fftaGoalClient->getResponse()->getContent(),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
        $ids = [];
        foreach ($licensesData['licences'] as $licenseData) {
            $html = $licenseData[9];
            $id = preg_replace("/.*FichePersonne_(\\d+)'.*/", '\\1', (string) $html);
            $ids[] = (int) $id;
        }

        return $ids;
    }

    public function findLicenseeIdFromCode(string $memberCode): ?int
    {
        $this->loginFftaGoal();

        $formUrl = sprintf(
            '%s/recherchesmulticriteres/rechercherpersonnes',
            $this->goalBaseUrl,
        );
        $crawler = $this->fftaGoalClient->request('GET', $formUrl);

        $form = $crawler
            ->filter('#formSearchPersonne')
            ->form(['inputAdherent' => $memberCode]);
        $crawler = $this->fftaGoalClient->submit($form);

        $requestUriComponents = parse_url(
            (string) $this->fftaGoalClient->getRequest()->getUri(),
        );
        if ('/personnes/show' === $requestUriComponents['path']) {
            parse_str($requestUriComponents['query'], $queryParameters);

            /** @var ?int $idPersonne */
            $idPersonne = $queryParameters['idPersonne'] ?? null;
            if ($idPersonne) {
                return (int) $idPersonne;
            }
        }
        $feedbackPanel = $crawler->filter('#feedbackPanel');
        if (
            $feedbackPanel->count() > 0
            && str_contains($feedbackPanel->text(), 'Aucune personne trouv')
        ) {
            throw new NotFoundHttpException();
        }

        throw new \ErrorException('Something went wrong during the request');
    }

    public function fetchLicenseeProfile(string $fftaId): FftaProfile
    {
        $this->loginFftaGoal();

        $url = sprintf(
            '%s/personnes/gettabpanel?personne.id=%s&tabId=Coordonnees_Personne',
            $this->goalBaseUrl,
            $fftaId,
        );
        $crawler = $this->fftaGoalClient->request('GET', $url);

        $identity = new FftaProfile();
        $identity
            ->setId($fftaId)
            ->setCodeAdherent(
                $this->clean(
                    $crawler
                        ->filterXPath(
                            "descendant-or-self::*[@id = 'identite.codeAdherent']",
                        )
                        ->text(),
                ),
            )
            ->setEmail(
                $this->clean(
                    $crawler
                        ->filterXPath("descendant-or-self::*[@id = 'email']")
                        ->text(),
                ),
            )
            ->setNom(
                $this->clean(
                    $crawler
                        ->filterXPath(
                            "descendant-or-self::*[@id = 'identite.nom']",
                        )
                        ->text(),
                    true,
                ),
            )
            ->setPrenom(
                $this->clean(
                    $crawler
                        ->filterXPath(
                            "descendant-or-self::*[@id = 'identite.prenom']",
                        )
                        ->text(),
                    true,
                ),
            );

        $mobileNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'mobile']",
        );
        $telephoneNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'telephone']",
        );
        $phone = null;
        if ($mobileNode->count() > 0) {
            $phone = $this->cleanPhoneNumber(
                $this->clean($mobileNode->text(), true),
            );
        } elseif ($telephoneNode->count() > 0) {
            $phone = $this->cleanPhoneNumber(
                $this->clean($telephoneNode->text(), true),
            );
        }
        $identity->setMobile($phone);

        $dateNaissanceNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'identite.dateNaissance']",
        );
        $identity->setDateNaissance(
            \DateTime::createFromFormat(
                'd/m/Y',
                $this->clean($dateNaissanceNode->text()),
            ),
        );

        $sexeNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'identite.sexe']",
        );
        $identity->setSexe(
            'Homme' === $this->clean($sexeNode->text())
                ? GenderType::MALE
                : GenderType::FEMALE,
        );

        return $identity;
    }

    public function fetchLicenseeProfilePicture(string $fftaId): ?string
    {
        $this->loginFftaGoal();

        $url = sprintf(
            '%s/personnes/gettabpanel?personne.id=%s&tabId=Coordonnees_Personne',
            $this->goalBaseUrl,
            $fftaId,
        );
        $crawler = $this->fftaGoalClient->request('GET', $url);

        $profilePictureCrawler = $crawler->filter('[alt="Photo Identité"]');
        if (0 === $profilePictureCrawler->count()) {
            return null;
        }

        $profilePictureUrl = $profilePictureCrawler->attr('src');
        $this->fftaGoalClient->request('GET', $profilePictureUrl);

        /** @var Response $response */
        $response = $this->fftaGoalClient->getResponse();
        $content = $response->getContent();

        if (200 !== $response->getStatusCode()) {
            throw new NotFoundHttpException(sprintf('Cannot fetch image at url %s', $profilePictureUrl));
        }
        $contentType = strtolower($response->getHeader('content-type'));
        if ('image/png' === $contentType) {
            $image = imagecreatefromstring($response->getContent());
            $stream = fopen('php://memory', 'w+');
            imagejpeg($image, $stream, 80);
            rewind($stream);
            $content = stream_get_contents($stream);
            $contentType = 'image/jpg';
        }
        if ('image/jpg' !== $contentType) {
            throw new BadRequestException(sprintf('Wrong mimetype for profile picture: %s', $contentType));
        }

        return $content;
    }

    /**
     * @return array<int, License>
     *
     * @throws \Exception
     */
    public function fetchLicenseeLicenses(
        int $fftaId,
        int $requestedSeason = null,
    ): array {
        $this->loginFftaGoal();

        $licences = [];

        $url = sprintf(
            '%s/personnes/gettabpanel?personne.id=%s&tabId=Licences_Personne',
            $this->goalBaseUrl,
            $fftaId,
        );
        $crawler = $this->fftaGoalClient->request('GET', $url);
        $seasons = $crawler->filter('.dd-item.dd2-item');
        $seasonIdx = 0;
        $seasons->each(function ($season) use (
            &$seasonIdx,
            &$licences,
            $requestedSeason,
        ) {
            $blockContent = $season->filter('.dd2-content');
            if (0 == $blockContent->count()) {
                return;
            }
            $blockTitle = $blockContent->text();
            $year = (int) str_replace('Saison ', '', $blockTitle);

            if ($requestedSeason && $requestedSeason !== $year) {
                return;
            }

            $structure = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.structure_%s']",
                    $seasonIdx,
                ),
            );
            if (0 == $structure->count()) {
                return;
            }
            $clubString = $structure->text();
            $clubId = preg_replace('/^(\d+).*/', '\1', $clubString);
            // Si pas archers de guyenne, on zappe
            // TODO rendre dynamique
            if ('1033093' != $clubId) {
                ++$seasonIdx;

                return;
            }
            $etatCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.etat_%s']",
                    $seasonIdx,
                ),
            );
            if ($etatCrawler->count() > 0) {
                $etatStr = $this->clean($etatCrawler->text());

                // Si la licence n'est pas active, elle a surement été annulée et remplacée et donc on zappe
                if ('Actif' !== $etatStr) {
                    ++$seasonIdx;

                    return;
                }
            }

            $licence = new License();
            $licence->setSeason($year);

            $libelleCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.libelle_%s']",
                    $seasonIdx,
                ),
            );
            if ($libelleCrawler->count() > 0) {
                $libelleStr = $this->clean($libelleCrawler->text());

                match ($libelleStr) {
                    'ADULTE Pratique en compétition' => $licence->setType(LicenseType::ADULTES_COMPETITION),
                    'ADULTE Pratique en club' => $licence->setType(LicenseType::ADULTES_CLUB),
                    'Jeune' => $licence->setType(LicenseType::JEUNES),
                    'Poussin' => $licence->setType(LicenseType::POUSSINS),
                    'Découverte' => $licence->setType(LicenseType::DECOUVERTE),
                    default => throw new \Exception(sprintf("Unknown licence type '%s'", $libelleStr)),
                };
            }

            $categorieAgeCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.categorieAge_%s']",
                    $seasonIdx,
                ),
            );
            if ($categorieAgeCrawler->count() > 0) {
                $categorieAgeStr = $this->clean($categorieAgeCrawler->text());

                switch ($categorieAgeStr) {
                    case 'Poussin':
                        $licence->setCategory(LicenseCategoryType::POUSSINS);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::POUSSIN,
                        );

                        break;
                    case 'Benjamin':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::BENJAMIN,
                        );

                        break;
                    case 'Minime':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::MINIME,
                        );

                        break;
                    case 'Cadet':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(LicenseAgeCategoryType::CADET);

                        break;
                    case 'Junior':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::JUNIOR,
                        );

                        break;
                    case 'Sénior 1':
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR_1,
                        );

                        break;
                    case 'Sénior 2':
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR_2,
                        );

                        break;
                    case 'Sénior 3':
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR_3,
                        );

                        break;
                    case 'Sénior':
                    case 'Senior':
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR,
                        );

                        break;
                    case 'U11':
                        $licence->setCategory(LicenseCategoryType::POUSSINS);
                        $licence->setAgeCategory(LicenseAgeCategoryType::U11);

                        break;
                    case 'U13':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(LicenseAgeCategoryType::U13);

                        break;
                    case 'U15':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(LicenseAgeCategoryType::U15);

                        break;
                    case 'U18':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(LicenseAgeCategoryType::U18);

                        break;
                    case 'U21':
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(LicenseAgeCategoryType::U21);

                        break;
                    case 'Vétéran':
                    case 'Veteran':
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::VETERAN,
                        );

                        break;
                    case 'Super Vétéran':
                    case 'Super Veteran':
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SUPER_VETERAN,
                        );

                        break;
                    default:
                        throw new \Exception(sprintf("Unknown Age Category '%s'", $categorieAgeStr));
                }
            }

            $activitesCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence%s.activite']",
                    $seasonIdx + 1,
                ),
            );
            if ($activitesCrawler->count() > 0) {
                $licenseActivities = new ArrayCollection(
                    $licence->getActivities(),
                );
                $listeActivites = explode(',', (string) $activitesCrawler->text());
                foreach ($listeActivites as $activite) {
                    $activiteStr = $this->clean($activite);
                    $activity = match ($activiteStr) {
                        'Arc Chasse' => LicenseActivityType::AC,
                        'Arc Classique' => LicenseActivityType::CL,
                        'Arc Droit' => LicenseActivityType::AD,
                        'Arc Nu' => LicenseActivityType::BB,
                        'Arc à Poulies' => LicenseActivityType::CO,
                        default => null,
                    };
                    if (!$activity) {
                        throw new \Exception(sprintf("Unknown Activity '%s'", $activiteStr));
                    }
                    if (!$licenseActivities->contains($activity)) {
                        $licenseActivities->add($activity);
                    }
                }
                $licence->setActivities($licenseActivities->toArray());
            }

            $licences[$year] = $licence;
            ++$seasonIdx;
        });

        return $licences;
    }

    /**
     * @return FftaEvent[]
     */
    public function fetchEvents(mixed $season): array
    {
        $this->loginFftaExtranet();

        $events = [];

        $crawler = $this->fftaExtranetClient->request(
            'POST',
            sprintf(
                '%s/gsportive/resultats-mesarchers.html',
                $this->extranetBaseUrl,
            ),
            [],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            sprintf('filtres[SaisonAnnee]=%s', $season),
        );
        $tableCrawler = $crawler->filter('table.orbe3');
        $eventLinesCrawler = $tableCrawler->filter('tbody tr');
        $eventLinesCrawler->each(function (Crawler $row) use (&$events) {
            $dateCell = $row->filter('td:nth-child(2)')->text();
            preg_match(
                '#(du|le) (\d+/\d+/\d+)(au (\d+/\d+/\d+))?#',
                $dateCell,
                $dateMatches,
            );
            $fromDate = $dateMatches[2];
            $toDate = $dateMatches[4] ?? $fromDate;

            $name = $row->filter('td:nth-child(3)')->text();
            $location = $row->filter('td:nth-child(4)')->text();
            $url = $row->attr('data-modal');

            $characteristicsCell = $row->filter('td:nth-child(5)')->html();
            $characteristics = preg_match(
                '/^<strong>(.*)<\\/strong>( - (.*))?<br>Saison \\d+<br>(.*<br>)+$/',
                $characteristicsCell,
                $characteristicsMatches,
            );
            $disciplineStr = $characteristicsMatches[1];
            $specifics = $characteristicsMatches[3];

            $discipline = DisciplineType::disciplineFromFftaExtranet(
                $disciplineStr,
            );

            $event = (new FftaEvent())
                ->setFrom(
                    \DateTimeImmutable::createFromFormat('!d/m/Y', $fromDate),
                )
                ->setTo(
                    \DateTimeImmutable::createFromFormat(
                        '!d/m/Y',
                        $toDate,
                    )->setTime(23, 59, 59),
                )
                ->setName($name)
                ->setLocation($location)
                ->setDiscipline($discipline)
                ->setSpecifics($specifics)
                ->setUrl($url);
            $events[] = $event;
        });

        return $events;
    }

    /**
     * @return FftaResult[]
     */
    public function fetchFftaResultsForFftaEvent(FftaEvent $fftaEvent): array
    {
        /** @var ?int $size */
        $size = null;

        /** @var ?int $distance */
        $distance = null;

        /** @var FftaResult[] $fftaResults */
        $fftaResults = [];

        $crawler = $this->fftaExtranetClient->request(
            'GET',
            $fftaEvent->getUrl(),
        );
        $tableCrawler = $crawler->filter('table.orbe3');
        $rowsCrawler = $tableCrawler->filter('tbody tr');
        $rowsCrawler->each(function (Crawler $row) use (
            &$fftaResults,
            $fftaEvent,
            &$distance,
            &$size,
        ) {
            $col = $row->filter('td:first-child');

            if ('ar al' === $col->attr('class')) {
                return;
            }

            $event = new ContestEvent();
            $event->setDiscipline($fftaEvent->getDiscipline());

            $category = $row->filter('td:nth-child(5)')->text();
            [$ageCategory, $activity] = CategoryParser::parseString($category);
            [$distance, $size] = Result::distanceForContestAndActivity(
                $event,
                $activity,
                $ageCategory,
            );

            $fftaResult = (new FftaResult())
                ->setPosition((int) $row->filter('td:nth-child(1)')->text())
                ->setName($row->filter('td:nth-child(2)')->text())
                ->setClub($row->filter('td:nth-child(3)')->text())
                ->setLicense($row->filter('td:nth-child(4)')->text())
                ->setCategory($row->filter('td:nth-child(5)')->text())
                ->setDistance($distance)
                ->setSize($size)
                ->setScore1((int) $row->filter('td:nth-child(6)')->text())
                ->setScore2((int) $row->filter('td:nth-child(7)')->text())
                ->setTotal((int) $row->filter('td:nth-child(8)')->text())
                ->setNb10((int) $row->filter('td:nth-child(9)')->text())
                ->setNb10p((int) $row->filter('td:nth-child(10)')->text());

            $fftaResults[] = $fftaResult;
        });

        return $fftaResults;
    }

    protected function clean(string $text, bool $ucwords = false): string
    {
        $text = trim($text, " \t\n\r\0\x0B\xC2\xA0");
        if ($ucwords) {
            $text = ucwords(mb_strtolower($text));
        }

        return $text;
    }

    protected function cleanPhoneNumber(string $number): string
    {
        $number = str_replace(' ', '', $number);

        return preg_replace('/^0/', '+33', $number);
    }

    protected function loginFftaGoal(): void
    {
        if ($this->fftaGoalIsConnected) {
            return;
        }
        $this->fftaGoalClient = new Client();
        $crawler = $this->fftaGoalClient->request(
            'GET',
            sprintf('%s/login', $this->goalBaseUrl),
        );
        $form = $crawler->selectButton('CONNEXION')->form();
        $this->fftaGoalClient->submit($form, [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        /** @var Response $response */
        $response = $this->fftaGoalClient->getResponse();
        if (200 !== $response->getStatusCode()) {
            throw new BadRequestHttpException('Bad response from FFTA login procedure');
        }
    }

    protected function loginFftaExtranet(): void
    {
        if ($this->fftaExtranetIsConnected) {
            return;
        }
        $this->fftaExtranetClient = new Client();
        $crawler = $this->fftaExtranetClient->request(
            'GET',
            sprintf('%s', $this->extranetBaseUrl),
        );

        $form = $crawler->filter('form[name=identification]')->form();
        $this->fftaExtranetClient->submit($form, [
            'login[identifiant]' => $this->username,
            'login[idpassword]' => $this->password,
        ]);

        /** @var Response $response */
        $response = $this->fftaExtranetClient->getResponse();
        if (200 !== $response->getStatusCode()) {
            throw new BadRequestHttpException('Bad response from FFTA login procedure');
        }
    }
}
