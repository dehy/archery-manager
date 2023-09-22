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
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FftaScrapper
{
    protected HttpBrowser $managerSpaceBrowser;
    protected bool $managerSpaceIsConnected = false;

    protected HttpBrowser $myFftaSpaceBrowser;
    protected bool $myFftaSpaceIsConnected = false;
    private string $managerSpaceBaseUrl = 'https://dirigeant.ffta.fr';
    private string $myFftaSpaceBaseUrl = 'https://monespace.ffta.fr';
    private int $structureId = 556;
    private int $season = 2024;
    private array $defaultParameters;

    private array $cachedResponse;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {
        if (!$this->username || !$this->password) {
            throw new \Exception('FFTA Credentials not set');
        }

        $this->defaultParameters = [
            'draw' => '1',
            'columns[0][data]' => 'code_adherent',
            'columns[0][name]' => 'personnes.code_adherent',
            'columns[0][searchable]' => 'true',
            'columns[0][orderable]' => 'true',
            'columns[0][search][value]' => '',
            'columns[0][search][regex]' => 'false',
            'columns[1][data]' => 'nom',
            'columns[1][name]' => 'personnes.nom',
            'columns[1][searchable]' => 'true',
            'columns[1][orderable]' => 'true',
            'columns[1][search][value]' => '',
            'columns[1][search][regex]' => 'false',
            'columns[2][data]' => 'prenom',
            'columns[2][name]' => 'personnes.prenom',
            'columns[2][searchable]' => 'true',
            'columns[2][orderable]' => 'true',
            'columns[2][search][value]' => '',
            'columns[2][search][regex]' => 'false',
            'columns[3][data]' => 'sexe',
            'columns[3][name]' => 'personnes.sexe',
            'columns[3][searchable]' => 'true',
            'columns[3][orderable]' => 'true',
            'columns[3][search][value]' => '',
            'columns[3][search][regex]' => 'false',
            'columns[4][data]' => 'ddn',
            'columns[4][name]' => 'personnes.ddn',
            'columns[4][searchable]' => 'true',
            'columns[4][orderable]' => 'true',
            'columns[4][search][value]' => '',
            'columns[4][search][regex]' => 'false',
            'columns[5][data]' => 'etat',
            'columns[5][name]' => 'licences.etat',
            'columns[5][searchable]' => 'true',
            'columns[5][orderable]' => 'true',
            'columns[5][search][value]' => '',
            'columns[5][search][regex]' => 'false',
            'columns[6][data]' => 'date_demande',
            'columns[6][name]' => 'licences.date_demande',
            'columns[6][searchable]' => 'true',
            'columns[6][orderable]' => 'true',
            'columns[6][search][value]' => '',
            'columns[6][search][regex]' => 'false',
            'columns[7][data]' => 'type_libelle',
            'columns[7][name]' => 'licences_types.libelle',
            'columns[7][searchable]' => 'true',
            'columns[7][orderable]' => 'true',
            'columns[7][search][value]' => '',
            'columns[7][search][regex]' => 'false',
            'columns[8][data]' => 'discipline',
            'columns[8][name]' => 'licences_types.libelle',
            'columns[8][searchable]' => 'true',
            'columns[8][orderable]' => 'true',
            'columns[8][search][value]' => '',
            'columns[8][search][regex]' => 'false',
            'columns[9][data]' => 'categorie_age',
            'columns[9][name]' => 'licences_types.libelle',
            'columns[9][searchable]' => 'true',
            'columns[9][orderable]' => 'true',
            'columns[9][search][value]' => '',
            'columns[9][search][regex]' => 'false',
            'columns[10][data]' => 'mutation',
            'columns[10][name]' => 'licences_types.libelle',
            'columns[10][searchable]' => 'true',
            'columns[10][orderable]' => 'true',
            'columns[10][search][value]' => '',
            'columns[10][search][regex]' => 'false',
            'columns[11][data]' => 'surclassement',
            'columns[11][name]' => 'licences_types.libelle',
            'columns[11][searchable]' => 'true',
            'columns[11][orderable]' => 'true',
            'columns[11][search][value]' => '',
            'columns[11][search][regex]' => 'false',
            'columns[12][data]' => 'mail',
            'columns[12][name]' => 'adresses.mail',
            'columns[12][searchable]' => 'true',
            'columns[12][orderable]' => 'true',
            'columns[12][search][value]' => '',
            'columns[12][search][regex]' => 'false',
            'columns[13][data]' => 'telephone',
            'columns[13][name]' => 'adresses.tel',
            'columns[13][searchable]' => 'true',
            'columns[13][orderable]' => 'true',
            'columns[13][search][value]' => '',
            'columns[13][search][regex]' => 'false',
            'columns[14][data]' => 'adresse',
            'columns[14][name]' => 'adresses.num_voie',
            'columns[14][searchable]' => 'true',
            'columns[14][orderable]' => 'true',
            'columns[14][search][value]' => '',
            'columns[14][search][regex]' => 'false',
            'columns[15][data]' => 'code_postal',
            'columns[15][name]' => 'adresses.code_postal_fr',
            'columns[15][searchable]' => 'true',
            'columns[15][orderable]' => 'true',
            'columns[15][search][value]' => '',
            'columns[15][search][regex]' => 'false',
            'columns[16][data]' => 'commune',
            'columns[16][name]' => 'adresses.commune',
            'columns[16][searchable]' => 'true',
            'columns[16][orderable]' => 'true',
            'columns[16][search][value]' => '',
            'columns[16][search][regex]' => 'false',
            'columns[17][data]' => 'representant_legal_1',
            'columns[17][name]' => 'licences.date_demande',
            'columns[17][searchable]' => 'true',
            'columns[17][orderable]' => 'true',
            'columns[17][search][value]' => '',
            'columns[17][search][regex]' => 'false',
            'columns[18][data]' => 'representant_legal_2',
            'columns[18][name]' => 'licences.date_demande',
            'columns[18][searchable]' => 'true',
            'columns[18][orderable]' => 'true',
            'columns[18][search][value]' => '',
            'columns[18][search][regex]' => 'false',
            'order[0][column]' => '1',
            'order[0][dir]' => 'asc',
            'start' => '0',
            // 'length' => '25',
            'search[value]' => '',
            'search[regex]' => 'false',
            'filtres[personne]' => '',
            'filtres[sexe]' => '',
            'filtres[saison]' => '',
            'filtres[etat]' => '*A',
            'filtres[structure]' => $this->structureId,
            '_' => time(),
        ];
    }

    private function setStructureId(int $structureId): void
    {
        $this->structureId = $structureId;
        $this->defaultParameters['filtres[structure]'] = (string) $structureId;
    }

    private function setSeason(int $season): void
    {
        $this->defaultParameters['filtres[saison]'] = (string) $season;
    }

    private function fetchLicenseeList(int $season): array
    {
        if (!isset($this->cachedResponse) || !$this->cachedResponse) {
            $this->loginManagerSpace();
            $this->setSeason($season);

            $parameters = $this->defaultParameters;
            $queryParameters = http_build_query($parameters, encoding_type: \PHP_QUERY_RFC3986);

            $url = sprintf(
                '%s/structures/fiche/%s/licencies/ajax?%s',
                $this->managerSpaceBaseUrl,
                $this->structureId,
                $queryParameters,
            );
            $this->managerSpaceBrowser->xmlHttpRequest(
                'GET',
                $url,
                [],
                [],
                [
                    'HTTP_ACCEPT' => 'application/json, text/javascript, */*; q=0.01',
                ],
            );

            $this->cachedResponse = json_decode(
                (string) $this->managerSpaceBrowser->getResponse()->getContent(),
                true,
                512,
                \JSON_THROW_ON_ERROR,
            );
        }

        return $this->cachedResponse;
    }

    /**
     * @return int[]
     *
     * @throws \JsonException
     * @throws \JsonException
     */
    public function fetchLicenseeIdList(int $season): array
    {
        $licenseeList = $this->fetchLicenseeList($season);

        $ids = [];
        foreach ($licenseeList['data'] as $licenseeData) {
            $ids[] = (int) $licenseeData['personne_id'];
        }

        return $ids;
    }

    public function findLicenseeIdFromCode(string $memberCode): ?int
    {
        $this->loginManagerSpace();

        $url = sprintf(
            '%s/personnes/recherche?personnes_q=%s',
            $this->managerSpaceBaseUrl,
            $memberCode,
        );
        $this->managerSpaceBrowser->followRedirects(false);
        $crawler = $this->managerSpaceBrowser->request('GET', $url);

        $searchStatusCode = $this->managerSpaceBrowser->getResponse()->getStatusCode();
        // if redirected to /personnes/fiche/$memberid, we got a match
        if (302 === $searchStatusCode) {
            $licenseeRecordUrl = $this->managerSpaceBrowser->getResponse()->getHeader('location');
            preg_match("%/fiche/(\d+)%", (string) $licenseeRecordUrl, $matches);

            return (int) $matches[1];
        } elseif (200 === $searchStatusCode) { // still on /personnes/recherche, no luck
            throw new NotFoundHttpException();
        }

        throw new \ErrorException('Something went wrong during the request');
    }

    public function fetchLicenseeProfile(int $fftaId, int $season): FftaProfile
    {
        $licenseeList = $this->fetchLicenseeList($season);

        $selectedLicenseeData = null;
        foreach ($licenseeList['data'] as $licenseeData) {
            if ($fftaId === $licenseeData['personne_id']) {
                $selectedLicenseeData = $licenseeData;
                break;
            }
        }

        $identity = new FftaProfile();
        $identity
            ->setId($fftaId)
            ->setCodeAdherent(
                $this->clean(
                    $selectedLicenseeData['code_adherent']
                ),
            )
            ->setEmail($this->clean($selectedLicenseeData['mail']))
            ->setNom(
                $this->clean(
                    $selectedLicenseeData['nom'],
                    true,
                ),
            )
            ->setPrenom(
                $this->clean(
                    $selectedLicenseeData['prenom'],
                    true,
                ),
            )
            ->setMobile($this->cleanPhoneNumber($selectedLicenseeData['telephone']));

        $identity->setDateNaissance(
            \DateTime::createFromFormat(
                'd/m/Y',
                $this->clean($selectedLicenseeData['ddn']),
            ),
        );

        $identity->setSexe(
            'Masculin' === $this->clean($selectedLicenseeData['sexe'])
                ? GenderType::MALE
                : GenderType::FEMALE,
        );

        return $identity;
    }

    public function fetchLicenseeProfilePicture(int $fftaId): ?string
    {
        $this->loginManagerSpace();

        $url = sprintf(
            '%s/personnes/fiche/%s/infos',
            $this->managerSpaceBaseUrl,
            $fftaId,
        );
        $crawler = $this->managerSpaceBrowser->request('GET', $url);

        $profilePictureCrawler = $crawler->filter('img.border-white.rounded-circle');
        if (0 === $profilePictureCrawler->count()) {
            return null;
        }

        $profilePictureUrl = $profilePictureCrawler->attr('src');
        $this->managerSpaceBrowser->request('GET', $profilePictureUrl);

        /** @var Response $response */
        $response = $this->managerSpaceBrowser->getResponse();
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
        if ('image/jpeg' !== $contentType && 'image/jpg' !== $contentType) {
            throw new BadRequestException(sprintf('Wrong mimetype for profile picture: %s', $contentType));
        }

        return $content;
    }

    /**
     * @throws \Exception
     */
    public function fetchLicenseeLicense(
        int $fftaId,
        int $season,
    ): License {
        $this->loginManagerSpace();

        $licenseeList = $this->fetchLicenseeList($season);

        $selectedLicenseeData = null;
        foreach ($licenseeList['data'] as $licenseeData) {
            if ($fftaId === $licenseeData['personne_id']) {
                $selectedLicenseeData = $licenseeData;
                break;
            }
        }

        $licence = new License();
        $licence->setSeason($season);
        $licence->setActivities([LicenseActivityType::CL]);

        match ($selectedLicenseeData['type_libelle']) {
            'Adulte pratique en compétition' => $licence->setType(LicenseType::ADULTES_COMPETITION),
            'Adulte pratique en club' => $licence->setType(LicenseType::ADULTES_CLUB),
            'Jeune' => $licence->setType(LicenseType::JEUNES),
            'Poussin' => $licence->setType(LicenseType::POUSSINS),
            'Découverte' => $licence->setType(LicenseType::DECOUVERTE),
            default => throw new \Exception(sprintf("Unknown licence type '%s'", $selectedLicenseeData['type_libelle'])),
        };

        switch ($selectedLicenseeData['categorie_age']) {
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
                throw new \Exception(sprintf("Unknown Age Category '%s'", $selectedLicenseeData['categorie_age']));
        }

        return $licence;
    }

    /**
     * @return FftaEvent[]
     */
    public function fetchEvents(mixed $season): array
    {
        $this->loginMyFftaSpace();

        $events = [];

        $crawler = $this->myFftaSpaceBrowser->request(
            'POST',
            sprintf(
                '%s/gsportive/resultats-mesarchers.html',
                $this->myFftaSpaceBaseUrl,
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

        $crawler = $this->myFftaSpaceBrowser->request(
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
        $numbers = explode('</br>', $number);
        $number = \count($numbers) > 1 ? $numbers[0] : $number;
        $number = str_replace(' ', '', $number);

        return preg_replace('/^0/', '+33', $number);
    }

    protected function loginManagerSpace(): void
    {
        if ($this->managerSpaceIsConnected) {
            return;
        }
        $this->managerSpaceBrowser = new HttpBrowser(HttpClient::create());
        $crawler = $this->managerSpaceBrowser->request(
            'GET',
            sprintf('%s/auth/login', $this->managerSpaceBaseUrl),
        );
        $form = $crawler->filter('#form-login')->form();
        $this->managerSpaceBrowser->submit($form, [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        /** @var Response $response */
        $response = $this->managerSpaceBrowser->getResponse();
        if (200 !== $response->getStatusCode()) {
            throw new BadRequestHttpException('Bad response from FFTA login procedure');
        }
    }

    protected function loginMyFftaSpace(): void
    {
        if ($this->myFftaSpaceIsConnected) {
            return;
        }
        $this->myFftaSpaceBrowser = new HttpBrowser(HttpClient::create());
        $crawler = $this->myFftaSpaceBrowser->request(
            'GET',
            sprintf('%s', $this->myFftaSpaceBaseUrl),
        );

        $form = $crawler->filter('#form-login')->form();
        $this->myFftaSpaceBrowser->submit($form, [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        /** @var Response $response */
        $response = $this->myFftaSpaceBrowser->getResponse();
        if (200 !== $response->getStatusCode()) {
            throw new BadRequestHttpException('Bad response from FFTA login procedure');
        }
    }
}
