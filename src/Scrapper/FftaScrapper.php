<?php

namespace App\Scrapper;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\FftaLicensee;
use App\Entity\License;
use App\Entity\Result;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use ErrorException;
use Exception;
use Goutte\Client;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FftaScrapper
{
    private string $goalBaseUrl = "https://ffta-goal.multimediabs.com";
    private string $extranetBaseUrl = "https://extranet.ffta.fr";

    protected Client $fftaGoalClient;
    protected bool $fftaGoalIsConnected = false;

    protected Client $fftaExtranetClient;
    protected bool $fftaExtranetIsConnected = false;

    public function __construct(
        private readonly string $username,
        private readonly string $password
    ) {
        if (!$this->username || !$this->password) {
            throw new Exception("FFTA Credentials not set");
        }
    }

    /**
     * @return int[]
     */
    public function fetchLicenseeIdList(int $season): array
    {
        $this->loginFftaGoal();

        $url = sprintf(
            "%s/licences/afficherlistelicencies?editionAttestation=&idSaison=%s&actifs=false",
            $this->goalBaseUrl,
            $season
        );
        $this->fftaGoalClient->xmlHttpRequest(
            "GET",
            $url,
            [],
            [],
            [
                "HTTP_ACCEPT" =>
                    "application/json, text/javascript, */*; q=0.01",
            ]
        );

        $licensesData = json_decode(
            $this->fftaGoalClient->getResponse()->getContent(),
            true
        );
        $ids = [];
        foreach ($licensesData["licences"] as $licenseData) {
            $html = $licenseData[9];
            $ids[] = preg_replace("/.*FichePersonne_(\d+)'.*/", "\\1", $html);
        }

        return $ids;
    }

    public function findLicenseeIdFromCode(string $memberCode): int
    {
        $this->loginFftaGoal();

        $formUrl = sprintf(
            "%s/recherchesmulticriteres/rechercherpersonnes",
            $this->goalBaseUrl
        );
        $crawler = $this->fftaGoalClient->request("GET", $formUrl);

        $form = $crawler
            ->filter("#formSearchPersonne")
            ->form(["inputAdherent" => $memberCode]);
        $crawler = $this->fftaGoalClient->submit($form);

        $requestUriComponents = parse_url(
            $this->fftaGoalClient->getRequest()->getUri()
        );
        if ($requestUriComponents["path"] === "/personnes/show") {
            parse_str($requestUriComponents["query"], $queryParameters);
            $idPersonne = $queryParameters["idPersonne"] ?? null;
            if ($idPersonne) {
                return $idPersonne;
            }
        }
        $feedbackPanel = $crawler->filter("#feedbackPanel");
        if (
            $feedbackPanel->count() > 0 &&
            str_contains($feedbackPanel->text(), "Aucune personne trouv")
        ) {
            throw new NotFoundHttpException();
        }

        throw new ErrorException("Something went wrong during the request");
    }

    public function fetchLicenseeProfile(string $fftaId): FftaProfile
    {
        $this->loginFftaGoal();

        $url = sprintf(
            "%s/personnes/gettabpanel?personne.id=%s&tabId=Coordonnees_Personne",
            $this->goalBaseUrl,
            $fftaId
        );
        $crawler = $this->fftaGoalClient->request("GET", $url);

        $identity = new FftaProfile();
        $identity
            ->setId($fftaId)
            ->setCodeAdherent(
                $this->clean(
                    $crawler
                        ->filterXPath(
                            "descendant-or-self::*[@id = 'identite.codeAdherent']"
                        )
                        ->text()
                )
            )
            ->setEmail(
                $this->clean(
                    $crawler
                        ->filterXPath("descendant-or-self::*[@id = 'email']")
                        ->text()
                )
            )
            ->setNom(
                $this->clean(
                    $crawler
                        ->filterXPath(
                            "descendant-or-self::*[@id = 'identite.nom']"
                        )
                        ->text(),
                    true
                )
            )
            ->setPrenom(
                $this->clean(
                    $crawler
                        ->filterXPath(
                            "descendant-or-self::*[@id = 'identite.prenom']"
                        )
                        ->text(),
                    true
                )
            );

        $mobileNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'mobile']"
        );
        $telephoneNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'telephone']"
        );
        $phone = null;
        if ($mobileNode->count() > 0) {
            $phone = $this->cleanPhoneNumber(
                $this->clean($mobileNode->text(), true)
            );
        } elseif ($telephoneNode->count() > 0) {
            $phone = $this->cleanPhoneNumber(
                $this->clean($telephoneNode->text(), true)
            );
        }
        $identity->setMobile($phone);

        $dateNaissanceNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'identite.dateNaissance']"
        );
        $identity->setDateNaissance(
            DateTime::createFromFormat(
                "d/m/Y",
                $this->clean($dateNaissanceNode->text())
            )
        );

        $sexeNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'identite.sexe']"
        );
        $identity->setSexe(
            $this->clean($sexeNode->text()) === "Homme"
                ? GenderType::MALE
                : GenderType::FEMALE
        );

        return $identity;
    }

    /**
     * @return array<int, License>
     * @throws Exception
     */
    public function fetchLicenseeLicenses(
        int $fftaId,
        ?int $requestedSeason = null
    ): array {
        $this->loginFftaGoal();

        $licences = [];

        $url = sprintf(
            "%s/personnes/gettabpanel?personne.id=%s&tabId=Licences_Personne",
            $this->goalBaseUrl,
            $fftaId
        );
        $crawler = $this->fftaGoalClient->request("GET", $url);
        $seasons = $crawler->filter(".dd-item.dd2-item");
        $seasonIdx = 0;
        $seasons->each(function ($season) use (
            &$seasonIdx,
            &$licences,
            $requestedSeason
        ) {
            $blockContent = $season->filter(".dd2-content");
            if ($blockContent->count() == 0) {
                return;
            }
            $blockTitle = $blockContent->text();
            $year = intval(str_replace("Saison ", "", $blockTitle));

            if ($requestedSeason && $requestedSeason !== $year) {
                return;
            }

            $structure = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.structure_%s']",
                    $seasonIdx
                )
            );
            if ($structure->count() == 0) {
                return;
            }
            //var_dump($structure_0);
            $clubString = $structure->text();
            $clubId = preg_replace("/^(\d+).*/", '\1', $clubString);
            // Si pas archers de guyenne, on zappe
            if ($clubId != "1033093") {
                return;
            }

            $licence = new License();
            $licence->setSeason($year);

            $libelleCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.libelle_%s']",
                    $seasonIdx
                )
            );
            if ($libelleCrawler->count() > 0) {
                $libelleStr = $this->clean($libelleCrawler->text());
                switch ($libelleStr) {
                    case "ADULTE Pratique en compétition":
                        $licence->setType(LicenseType::ADULTES_COMPETITION);
                        break;
                    case "ADULTE Pratique en club":
                        $licence->setType(LicenseType::ADULTES_CLUB);
                        break;
                    case "Jeune":
                        $licence->setType(LicenseType::JEUNES);
                        break;
                    case "Poussin":
                        $licence->setType(LicenseType::POUSSINS);
                        break;
                    case "Découverte":
                        $licence->setType(LicenseType::DECOUVERTE);
                        break;
                    default:
                        throw new Exception(
                            sprintf("Unknown licence type '%s'", $libelleStr)
                        );
                }
            }

            $categorieAgeCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.categorieAge_%s']",
                    $seasonIdx
                )
            );
            if ($categorieAgeCrawler->count() > 0) {
                $categorieAgeStr = $this->clean($categorieAgeCrawler->text());
                switch ($categorieAgeStr) {
                    case "Poussin":
                        $licence->setCategory(LicenseCategoryType::POUSSINS);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::POUSSIN
                        );
                        break;
                    case "Benjamin":
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::BENJAMIN
                        );
                        break;
                    case "Minime":
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::MINIME
                        );
                        break;
                    case "Cadet":
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(LicenseAgeCategoryType::CADET);
                        break;
                    case "Junior":
                        $licence->setCategory(LicenseCategoryType::JEUNES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::JUNIOR
                        );
                        break;
                    case "Sénior 1":
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR_1
                        );
                        break;
                    case "Sénior 2":
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR_2
                        );
                        break;
                    case "Sénior 3":
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR_3
                        );
                        break;
                    case "Sénior":
                    case "Senior":
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SENIOR
                        );
                        break;
                    case "Vétéran":
                    case "Veteran":
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::VETERAN
                        );
                        break;
                    case "Super Vétéran":
                    case "Super Veteran":
                        $licence->setCategory(LicenseCategoryType::ADULTES);
                        $licence->setAgeCategory(
                            LicenseAgeCategoryType::SUPER_VETERAN
                        );
                        break;
                    default:
                        throw new Exception(
                            sprintf(
                                "Unknown Age Category '%s'",
                                $categorieAgeStr
                            )
                        );
                }
            }

            $activitesCrawler = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence%s.activite']",
                    $seasonIdx + 1
                )
            );
            if ($activitesCrawler->count() > 0) {
                $licenseActivities = new ArrayCollection(
                    $licence->getActivities()
                );
                $listeActivites = explode(",", $activitesCrawler->text());
                foreach ($listeActivites as $activite) {
                    $activiteStr = $this->clean($activite);
                    $activity = match ($activiteStr) {
                        "Arc Chasse" => LicenseActivityType::AC,
                        "Arc Classique" => LicenseActivityType::CL,
                        "Arc Droit" => LicenseActivityType::AD,
                        "Arc Nu" => LicenseActivityType::BB,
                        "Arc à Poulies" => LicenseActivityType::CO,
                    };
                    if (!$activity) {
                        throw new Exception(
                            sprintf("Unknown Activity '%s'", $activiteStr)
                        );
                    }
                    if (!$licenseActivities->contains($activity)) {
                        $licenseActivities->add($activity);
                    }
                }
                $licence->setActivities($licenseActivities->toArray());
            }

            $licences[$year] = $licence;
            $seasonIdx += 1;
        });

        return $licences;
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
        $number = str_replace(" ", "", $number);
        return preg_replace("/^0/", "+33", $number);
    }

    protected function loginFftaGoal(): void
    {
        if ($this->fftaGoalIsConnected) {
            return;
        }
        $this->fftaGoalClient = new Client();
        $crawler = $this->fftaGoalClient->request(
            "GET",
            sprintf("%s/login", $this->goalBaseUrl)
        );
        $form = $crawler->selectButton("CONNEXION")->form();
        $this->fftaGoalClient->submit($form, [
            "username" => $this->username,
            "password" => $this->password,
        ]);
        /** @var Response $response */
        $response = $this->fftaGoalClient->getResponse();
        if ($response->getStatusCode() !== 200) {
            throw new BadRequestHttpException(
                "Bad response from FFTA login procedure"
            );
        }
    }

    /**
     * @return FftaEvent[]
     */
    public function fetchEvents($season): array
    {
        $this->loginFftaExtranet();

        $events = [];

        $crawler = $this->fftaExtranetClient->request(
            "POST",
            sprintf(
                "%s/gsportive/resultats-mesarchers.html",
                $this->extranetBaseUrl
            ),
            [],
            [],
            [
                "HTTP_CONTENT_TYPE" => "application/x-www-form-urlencoded",
            ],
            sprintf("filtres[SaisonAnnee]=%s", $season)
        );
        $tableCrawler = $crawler->filter("table.orbe3");
        $eventLinesCrawler = $tableCrawler->filter("tbody tr");
        $eventLinesCrawler->each(function (Crawler $row) use (&$events) {
            $dateCell = $row->filter("td:nth-child(2)")->text();
            preg_match(
                "#(du|le) (\d+/\d+/\d+)(au (\d+/\d+/\d+))?#",
                $dateCell,
                $dateMatches
            );
            $fromDate = $dateMatches[2];
            $toDate = $dateMatches[4] ?? $fromDate;

            $name = $row->filter("td:nth-child(3)")->text();
            $location = $row->filter("td:nth-child(4)")->text();
            $url = $row->attr("data-modal");

            $characteristicsCell = $row->filter("td:nth-child(5)")->html();
            $characteristics = preg_match(
                "/^<strong>(.*)<\/strong>( - (.*))?<br>Saison \d+<br>(.*<br>)+$/",
                $characteristicsCell,
                $characteristicsMatches
            );
            $disciplineStr = $characteristicsMatches[1];
            $specifics = $characteristicsMatches[3];

            $discipline = DisciplineType::disciplineFromFftaExtranet(
                $disciplineStr
            );

            $event = (new FftaEvent())
                ->setFrom(
                    DateTimeImmutable::createFromFormat("!d/m/Y", $fromDate)
                )
                ->setTo(
                    DateTimeImmutable::createFromFormat(
                        "!d/m/Y",
                        $toDate
                    )->setTime(23, 59, 59)
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
            "GET",
            $fftaEvent->getUrl()
        );
        $tableCrawler = $crawler->filter("table.orbe3");
        $rowsCrawler = $tableCrawler->filter("tbody tr");
        $rowsCrawler->each(function (Crawler $row) use (
            &$fftaResults,
            $fftaEvent,
            &$distance,
            &$size
        ) {
            $col = $row->filter("td:first-child");

            if ($col->attr("class") === "ar al") {
                return;
            }

            $category = $row->filter("td:nth-child(5)")->text();
            list($ageCategory, $activity) = CategoryParser::parseString(
                $category
            );
            list($distance, $size) = Result::distanceForContestTypeAndActivity(
                ContestType::FEDERAL,
                $fftaEvent->getDiscipline(),
                $activity,
                $ageCategory
            );

            $fftaResult = (new FftaResult())
                ->setPosition((int) $row->filter("td:nth-child(1)")->text())
                ->setName($row->filter("td:nth-child(2)")->text())
                ->setClub($row->filter("td:nth-child(3)")->text())
                ->setLicense($row->filter("td:nth-child(4)")->text())
                ->setCategory($row->filter("td:nth-child(5)")->text())
                ->setDistance($distance)
                ->setSize($size)
                ->setScore1((int) $row->filter("td:nth-child(6)")->text())
                ->setScore2((int) $row->filter("td:nth-child(7)")->text())
                ->setTotal((int) $row->filter("td:nth-child(8)")->text())
                ->setNb10((int) $row->filter("td:nth-child(9)")->text())
                ->setNb10p((int) $row->filter("td:nth-child(10)")->text());

            $fftaResults[] = $fftaResult;
        });

        return $fftaResults;
    }

    protected function loginFftaExtranet(): void
    {
        if ($this->fftaExtranetIsConnected) {
            return;
        }
        $this->fftaExtranetClient = new Client();
        $crawler = $this->fftaExtranetClient->request(
            "GET",
            sprintf("%s", $this->extranetBaseUrl)
        );

        $form = $crawler->filter("form[name=identification]")->form();
        $this->fftaExtranetClient->submit($form, [
            "login[identifiant]" => $this->username,
            "login[idpassword]" => $this->password,
        ]);
        /** @var Response $response */
        $response = $this->fftaExtranetClient->getResponse();
        if ($response->getStatusCode() !== 200) {
            throw new BadRequestHttpException(
                "Bad response from FFTA login procedure"
            );
        }
    }
}
