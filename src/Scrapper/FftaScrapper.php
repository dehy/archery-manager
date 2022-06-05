<?php

namespace App\Scrapper;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\License;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use ErrorException;
use Exception;
use Goutte\Client;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FftaScrapper
{
    private string $baseUrl = "https://ffta-goal.multimediabs.com";

    protected Client $client;

    public function __construct(
        private readonly string $username,
        private readonly string $password
    ) {
        if (!$this->username || !$this->password) {
            throw new Exception("FFTA Credentials not set");
        }
        $this->client = new Client();
        $this->login();
    }

    public function fetchLicenseeList(int $season): array
    {
        $url = sprintf(
            "%s/licences/afficherlistelicencies?editionAttestation=&idSaison=%s&actifs=false",
            $this->baseUrl,
            $season
        );
        $this->client->xmlHttpRequest(
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
            $this->client->getResponse()->getContent(),
            true
        );
        $fftaIdentities = [];
        foreach ($licensesData["licences"] as $licenseData) {
            $html = $licenseData[9];
            $identity = [
                "code" => $licenseData[0],
                "name" => $licenseData[1],
                "id" => preg_replace(
                    "/.*FichePersonne_(\d+)'.*/",
                    "\\1",
                    $html
                ),
            ];
            $fftaIdentities[] = $identity;
        }

        return $fftaIdentities;
    }

    public function findLicenseeIdFromCode(string $memberCode): int
    {
        $formUrl = sprintf(
            "%s/recherchesmulticriteres/rechercherpersonnes",
            $this->baseUrl
        );
        $crawler = $this->client->request("GET", $formUrl);

        $form = $crawler
            ->filter("#formSearchPersonne")
            ->form(["inputAdherent" => $memberCode]);
        $crawler = $this->client->submit($form);

        $requestUriComponents = parse_url(
            $this->client->getRequest()->getUri()
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

    public function fetchLicenceeIdentity(string $fftaId): FftaIdentity
    {
        $url = sprintf(
            "%s/personnes/gettabpanel?personne.id=%s&tabId=Coordonnees_Personne",
            $this->baseUrl,
            $fftaId
        );
        $crawler = $this->client->request("GET", $url);

        $identity = new FftaIdentity();
        $identity->codeAdherent = $this->clean(
            $crawler
                ->filterXPath(
                    "descendant-or-self::*[@id = 'identite.codeAdherent']"
                )
                ->text()
        );
        $identity->email = $this->clean(
            $crawler
                ->filterXPath("descendant-or-self::*[@id = 'email']")
                ->text()
        );
        $identity->nom = $this->clean(
            $crawler
                ->filterXPath("descendant-or-self::*[@id = 'identite.nom']")
                ->text(),
            true
        );
        $identity->prenom = $this->clean(
            $crawler
                ->filterXPath("descendant-or-self::*[@id = 'identite.prenom']")
                ->text(),
            true
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
        $identity->mobile = $phone;

        $dateNaissanceNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'identite.dateNaissance']"
        );
        $identity->dateNaissance = DateTime::createFromFormat(
            "d/m/Y",
            $this->clean($dateNaissanceNode->text())
        );

        $sexeNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'identite.sexe']"
        );
        $identity->sexe =
            $this->clean($sexeNode->text()) === "Homme"
                ? GenderType::MALE
                : GenderType::FEMALE;

        return $identity;
    }

    /**
     * @return License[]
     * @throws Exception
     */
    public function fetchLicenseeLicenses(int $fftaId): array
    {
        $licences = [];

        $url = sprintf(
            "%s/personnes/gettabpanel?personne.id=%s&tabId=Licences_Personne",
            $this->baseUrl,
            $fftaId
        );
        $crawler = $this->client->request("GET", $url);
        $seasons = $crawler->filter(".dd-item.dd2-item");
        $seasonIdx = 0;
        $seasons->each(function ($season) use (&$seasonIdx, &$licences) {
            $blockContent = $season->filter(".dd2-content");
            if ($blockContent->count() == 0) {
                return;
            }
            $blockTitle = $blockContent->text();
            $year = intval(str_replace("Saison ", "", $blockTitle));

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

            $libelle = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.libelle_%s']",
                    $seasonIdx
                )
            );
            if ($libelle->count() > 0) {
                $libelleStr = $this->clean($libelle->text());
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

            $categorieAge = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence.categorieAge_%s']",
                    $seasonIdx
                )
            );
            if ($categorieAge->count() > 0) {
                $categorieAgeStr = $this->clean($categorieAge->text());
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

                $licences[] = $licence;
            }

            $activites = $season->filterXPath(
                sprintf(
                    "descendant-or-self::*[@id = 'licence%s.activite']",
                    $seasonIdx + 1
                )
            );
            if ($activites->count() > 0) {
                $licenseActivities = new ArrayCollection(
                    $licence->getActivities()
                );
                $listeActivites = explode(",", $activites->text());
                foreach ($listeActivites as $activite) {
                    $activiteStr = $this->clean($activite);
                    $activity = match ($activiteStr) {
                        "Arc Classique" => LicenseActivityType::CLASSIC,
                        "Arc Nu" => LicenseActivityType::BARE,
                        "Arc à Poulies" => LicenseActivityType::COMPOUND,
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

    protected function login(): void
    {
        $crawler = $this->client->request(
            "GET",
            sprintf("%s/login", $this->baseUrl)
        );
        $form = $crawler->selectButton("CONNEXION")->form();
        $this->client->submit($form, [
            "username" => $this->username,
            "password" => $this->password,
        ]);
        /** @var Response $response */
        $response = $this->client->getResponse();
        if ($response->getStatusCode() !== 200) {
            throw new BadRequestHttpException(
                "Bad response from FFTA login procedure"
            );
        }
    }
}
