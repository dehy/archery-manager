<?php

declare(strict_types=1);

namespace App\Scrapper;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Scrapes publicly available competition data from https://www.ffta.fr/competitions.
 * No authentication is required.
 */
class FftaPublicCompetitionsScrapper
{
    private const string BASE_URL = 'https://www.ffta.fr';

    private const string COMPETITIONS_PATH = '/competitions';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Fetches competitions visible in the given department codes for a date range.
     *
     * @param list<string> $departmentCodes e.g. ['33', '64']
     *
     * @return FftaPublicEvent[]
     */
    public function fetchCompetitions(
        array $departmentCodes,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): array {
        $query = [
            'search' => '',
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'discipline' => 'All',
            'univers' => '299',
            'inter' => 'All',
            'sort_by' => 'start',
            'sort_order' => 'ASC',
        ];

        foreach ($departmentCodes as $code) {
            $query['dep[]'][] = $code;
        }

        $url = self::BASE_URL.self::COMPETITIONS_PATH.'?'.$this->buildQuery($departmentCodes, $query);

        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        $events = [];

        $crawler->filter('article.competition, .view-competitions .views-row')->each(
            function (Crawler $node) use (&$events): void {
                $event = $this->parseCompetitionRow($node);
                if ($event instanceof FftaPublicEvent) {
                    $events[] = $event;
                }
            }
        );

        // Fallback: try parsing as simple section list
        if ([] === $events) {
            $events = $this->parseCompetitionList($crawler);
        }

        return $events;
    }

    /**
     * Fetches and parses detail from a single FFTA event page.
     *
     * @param int $fftaEventId the numeric event ID from /epreuve/XXXXX
     */
    public function fetchEventDetail(int $fftaEventId): ?FftaPublicEvent
    {
        return $this->parseDetailPage($fftaEventId);
    }

    private function buildQuery(array $departmentCodes, array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if (\is_array($value)) {
                continue;
            }

            $parts[] = urlencode($key).'='.urlencode((string) $value);
        }

        foreach ($departmentCodes as $code) {
            $parts[] = 'dep%5B%5D='.urlencode($code);
        }

        return implode('&', $parts);
    }

    /**
     * Try to parse competitions from the HTML page.
     * The FFTA website renders a list of competitions as sections with heading links.
     *
     * @return FftaPublicEvent[]
     */
    private function parseCompetitionList(Crawler $crawler): array
    {
        $events = [];

        // Each section starts with ## [Name](url) pattern – find all <h2> or <h3> with links to /epreuve/
        $crawler->filter('h2 a, h3 a')->each(function (Crawler $link) use (&$events): void {
            $href = $link->attr('href');
            if (null === $href) {
                return;
            }

            if (!preg_match('#/epreuve/(\d+)#', $href, $matches)) {
                return;
            }

            $fftaEventId = (int) $matches[1];
            $event = $this->parseDetailPage($fftaEventId);
            if ($event instanceof FftaPublicEvent) {
                $events[] = $event;
            }
        });

        return $events;
    }

    private function parseCompetitionRow(Crawler $node): ?FftaPublicEvent
    {
        $link = $node->filter('a')->first();
        if (!$link->count()) {
            return null;
        }

        $href = $link->attr('href') ?? '';
        if (!preg_match('#/epreuve/(\d+)#', $href, $matches)) {
            return null;
        }

        return $this->parseDetailPage((int) $matches[1]);
    }

    private function parseDetailPage(int $fftaEventId): ?FftaPublicEvent
    {
        $url = self::BASE_URL.'/epreuve/'.$fftaEventId;
        $html = $this->fetchHtml($url);
        if ('' === $html) {
            return null;
        }

        $crawler = new Crawler($html);

        // Extract the page title (event name)
        $name = trim($crawler->filter('h1')->first()->text(''));
        if ('' === $name) {
            return null;
        }

        // Extract structured fields from the detail page
        $fields = $this->extractDetailFields($crawler);

        $discipline = $this->mapDiscipline($fields['discipline'] ?? '');
        if (null === $discipline) {
            return null;
        }

        $dates = $this->parseDates($crawler);
        if (null === $dates) {
            return null;
        }

        $contestType = $this->mapContestType($fields['championnat'] ?? '');
        $city = $fields['lieu'] ?? '';
        $address = $fields['address'] ?? $city;
        $comiteDep = $fields['comite_departemental'] ?? '';
        $comiteReg = $fields['comite_regional'] ?? '';
        $organizer = $fields['organisateur'] ?? '';

        return new FftaPublicEvent(
            fftaEventId: $fftaEventId,
            fftaUrl: $url,
            name: $name,
            startsAt: $dates['start'],
            endsAt: $dates['end'],
            city: $city,
            address: $address,
            discipline: $discipline,
            comiteDepartemental: $comiteDep,
            comiteRegional: $comiteReg,
            organizerName: $organizer,
            contestType: $contestType,
        );
    }

    /**
     * Extracts key/value fields from the detail page structure.
     * The FFTA detail page renders fields as label: value pairs inside paragraphs or table cells.
     *
     * @return array<string, string>
     */
    private function extractDetailFields(Crawler $crawler): array
    {
        $fields = [];

        // Try to find field rows in the main content area
        $content = $crawler->filter('.field--name-body, .event-content, main article, .node__content');
        if (!$content->count()) {
            $content = $crawler->filter('main, body');
        }

        $text = $content->first()->text('');

        $patterns = [
            'discipline' => '/Discipline\s*:\s*([^\n\r]+)/i',
            'championnat' => '/Championnat\s*:\s*([^\n\r]+)/i',
            'comite_regional' => '/Comité régional\s*:\s*([^\n\r]+)/i',
            'comite_departemental' => '/Comité départemental\s*:\s*([^\n\r]+)/i',
            'organisateur' => '/Organisateur\s*:\s*([^\n\r]+)/i',
            'lieu' => '/Lieu\s*:\s*([^\n\r]+)/i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $fields[$key] = trim($m[1]);
            }
        }

        // Try to parse the full address block after "Lieu"
        if (preg_match('/Lieu\s*:\s*([^\n\r]+)\n([^\n\r]+)\n(\d{5}[^\n\r]*)\n?([A-Z]+)?/i', $text, $m)) {
            $fields['lieu'] = trim($m[1]);
            $fields['address'] = trim($m[1]."\n".$m[2]."\n".$m[3]);
        }

        return $fields;
    }

    /**
     * Parses competition dates from the detail page.
     * Formats found: "LE 05 AVRIL 2026", "DU 11 AU 12 AVRIL 2026".
     *
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}|null
     */
    private function parseDates(Crawler $crawler): ?array
    {
        $text = $crawler->filter('main, body')->first()->text('');

        $frenchMonths = [
            'janvier' => '01', 'février' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'août' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'décembre' => '12',
        ];

        $monthPattern = implode('|', array_keys($frenchMonths));

        // "DU DD AU DD MOIS YYYY"
        if (preg_match('/DU\s+(\d{1,2})\s+AU\s+(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})/i', $text, $m)) {
            $month = $frenchMonths[strtolower($m[3])];
            $start = \DateTimeImmutable::createFromFormat('d/m/Y', \sprintf('%02d/%s/%s', $m[1], $month, $m[4]));
            $end = \DateTimeImmutable::createFromFormat('d/m/Y', \sprintf('%02d/%s/%s', $m[2], $month, $m[4]));
            if ($start && $end) {
                return ['start' => $start, 'end' => $end];
            }
        }

        // "LE DD MOIS YYYY"
        if (preg_match('/LE\s+(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})/i', $text, $m)) {
            $month = $frenchMonths[strtolower($m[2])];
            $start = \DateTimeImmutable::createFromFormat('d/m/Y', \sprintf('%02d/%s/%s', $m[1], $month, $m[3]));
            if ($start) {
                return ['start' => $start, 'end' => $start];
            }
        }

        return null;
    }

    private function mapDiscipline(string $fftaDiscipline): ?string
    {
        $map = [
            "tir à l'arc extérieur" => DisciplineType::TARGET,
            'tir 3d' => DisciplineType::THREE_D,
            'tir nature' => DisciplineType::NATURE,
            'tir en salle' => DisciplineType::INDOOR,
            'tir à 18m' => DisciplineType::INDOOR,
            'run archery' => DisciplineType::RUN,
            'para-tir' => DisciplineType::PARA,
        ];

        $normalized = strtolower(trim($fftaDiscipline));
        foreach ($map as $key => $value) {
            if (str_contains($normalized, $key)) {
                return $value;
            }
        }

        return null;
    }

    private function mapContestType(string $championnat): ?string
    {
        $normalized = strtolower($championnat);
        if (str_contains($normalized, 'équipe') || str_contains($normalized, 'equipe')) {
            return ContestType::TEAM;
        }

        if (str_contains($normalized, 'individuel')) {
            return ContestType::INDIVIDUAL;
        }

        return null;
    }

    private function fetchHtml(string $url): string
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; ArcheryManager/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'fr-FR,fr;q=0.9',
                ],
                'timeout' => 30,
            ]);

            if (200 !== $response->getStatusCode()) {
                return '';
            }

            return $response->getContent();
        } catch (\Throwable) {
            return '';
        }
    }
}
