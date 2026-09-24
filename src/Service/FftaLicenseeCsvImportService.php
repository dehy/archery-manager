<?php

declare(strict_types=1);

namespace App\Service;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\Licensee;
use App\Helper\LicenseHelper;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class FftaLicenseeCsvImportService
{
    private const string STATE_ACTIVE = 'Active';

    private const array REQUIRED_HEADERS = [
        'Code Adhérent',
        'Nom',
        'Prénom',
        'Sexe',
        'Date de naissance',
        'État',
        'Valide depuis le',
        'Type',
        'Catégorie âge',
        'Email',
    ];

    public function __construct(
        private LicenseeRepository $licenseeRepository,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @return array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>}
     */
    public function createPreview(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'rb');
        if (false === $handle) {
            throw new \RuntimeException('Impossible de lire le fichier CSV importé.');
        }

        try {
            // The BOM sits before the first field's opening quote, so it must be
            // stripped before fgetcsv() parses the header row, otherwise the quote
            // is treated as literal content instead of a CSV enclosure.
            if ("\xEF\xBB\xBF" !== fread($handle, 3)) {
                rewind($handle);
            }

            $headers = fgetcsv($handle, separator: ';', escape: '');
            if (false === $headers) {
                throw new \RuntimeException('Le fichier CSV est vide.');
            }

            $headerIndexes = $this->headerIndexes($headers);
            $rawRows = $this->readRows($handle, $headerIndexes);
        } finally {
            fclose($handle);
        }

        $licenseesByCode = $this->licenseesByCode(array_column($rawRows, 'fftaMemberCode'));
        $usersByEmail = $this->usersByEmail(array_filter(array_column($rawRows, 'email')));

        return $this->reconcileRows($rawRows, $licenseesByCode, $usersByEmail);
    }

    /**
     * @param list<string|null> $headers
     *
     * @return array<string, int>
     */
    private function headerIndexes(array $headers): array
    {
        $indexes = [];
        foreach ($headers as $index => $header) {
            $indexes[trim((string) $header)] = $index;
        }

        $missingHeaders = array_diff(self::REQUIRED_HEADERS, array_keys($indexes));
        if ([] !== $missingHeaders) {
            throw new \RuntimeException(\sprintf('Colonnes CSV manquantes : %s.', implode(', ', $missingHeaders)));
        }

        return $indexes;
    }

    /**
     * @param resource $handle
     * @param array<string, int> $headerIndexes
     *
     * @return list<array{line: int, fftaMemberCode: string, firstname: string, lastname: string, gender: string, birthdate: string, state: string, validFrom: string, type: string, ageCategory: string, email: string|null}>
     */
    private function readRows($handle, array $headerIndexes): array
    {
        $rows = [];
        $line = 1;
        while (false !== ($columns = fgetcsv($handle, separator: ';', escape: ''))) {
            ++$line;
            if ([null] === $columns) {
                continue;
            }

            $rows[] = [
                'line' => $line,
                'fftaMemberCode' => $this->value($columns, $headerIndexes, 'Code Adhérent'),
                'firstname' => $this->value($columns, $headerIndexes, 'Prénom'),
                'lastname' => $this->value($columns, $headerIndexes, 'Nom'),
                'gender' => $this->value($columns, $headerIndexes, 'Sexe'),
                'birthdate' => $this->value($columns, $headerIndexes, 'Date de naissance'),
                'state' => $this->value($columns, $headerIndexes, 'État'),
                'validFrom' => $this->value($columns, $headerIndexes, 'Valide depuis le'),
                'type' => $this->value($columns, $headerIndexes, 'Type'),
                'ageCategory' => $this->value($columns, $headerIndexes, 'Catégorie âge'),
                'email' => $this->emailValue($columns, $headerIndexes),
            ];
        }

        return $rows;
    }

    /**
     * @param list<string|null> $columns
     * @param array<string, int> $headerIndexes
     */
    private function value(array $columns, array $headerIndexes, string $header): string
    {
        return trim((string) ($columns[$headerIndexes[$header]] ?? ''));
    }

    /**
     * @param list<string|null> $columns
     * @param array<string, int> $headerIndexes
     */
    private function emailValue(array $columns, array $headerIndexes): ?string
    {
        $email = strtolower($this->value($columns, $headerIndexes, 'Email'));

        return '' === $email ? null : $email;
    }

    /**
     * @param list<string> $codes
     *
     * @return array<string, Licensee>
     */
    private function licenseesByCode(array $codes): array
    {
        $licenseesByCode = [];
        foreach (array_unique($codes) as $code) {
            $licensee = $this->licenseeRepository->findOneByCode($code);
            if ($licensee instanceof Licensee) {
                $licenseesByCode[$code] = $licensee;
            }
        }

        return $licenseesByCode;
    }

    /**
     * @param list<string> $emails
     *
     * @return array<string, int>
     */
    private function usersByEmail(array $emails): array
    {
        $usersByEmail = [];
        foreach (array_unique($emails) as $email) {
            $user = $this->userRepository->findOneByEmail($email);
            if (null !== $user?->getId()) {
                $usersByEmail[$email] = $user->getId();
            }
        }

        return $usersByEmail;
    }

    /**
     * @param list<array{line: int, fftaMemberCode: string, firstname: string, lastname: string, gender: string, birthdate: string, state: string, validFrom: string, type: string, ageCategory: string, email: string|null}> $rawRows
     * @param array<string, Licensee> $licenseesByCode
     * @param array<string, int> $usersByEmail
     *
     * @return array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>}
     */
    private function reconcileRows(array $rawRows, array $licenseesByCode, array $usersByEmail): array
    {
        $preview = ['rows' => [], 'alreadyLicensed' => [], 'skipped' => [], 'errors' => []];
        $newLicenseeRowIndexesByEmail = [];
        $seenMemberCodes = [];

        foreach ($rawRows as $rawRow) {
            if (self::STATE_ACTIVE !== $rawRow['state']) {
                $preview['skipped'][] = ['line' => $rawRow['line'], 'reason' => \sprintf('État FFTA « %s » non importé.', $rawRow['state'])];
                continue;
            }

            try {
                $row = $this->mapRow($rawRow);
            } catch (\InvalidArgumentException $exception) {
                $preview['errors'][] = ['line' => $rawRow['line'], 'reason' => $exception->getMessage()];
                continue;
            }

            if (isset($seenMemberCodes[$row['fftaMemberCode']])) {
                $preview['errors'][] = ['line' => $row['line'], 'reason' => \sprintf('Le code adhérent est déjà présent à la ligne %d.', $seenMemberCodes[$row['fftaMemberCode']])];
                continue;
            }

            $seenMemberCodes[$row['fftaMemberCode']] = $row['line'];

            $licensee = $licenseesByCode[$row['fftaMemberCode']] ?? null;
            if ($licensee instanceof Licensee) {
                if ($licensee->getLicenseForSeason($row['season']) instanceof \App\Entity\License) {
                    $row['licenseeId'] = $licensee->getId();
                    $row['alreadyLicensed'] = true;
                    $preview['alreadyLicensed'][] = $row;
                    continue;
                }

                $row['licenseeId'] = $licensee->getId();
                $row['userId'] = $licensee->getUser()?->getId();
                $row['userChoice'] = 'existing-licensee';
            } else {
                if (null === $row['email']) {
                    $preview['errors'][] = ['line' => $row['line'], 'reason' => 'Une adresse email est requise pour créer un nouveau licencié.'];
                    continue;
                }

                $row['licenseeId'] = null;
                $row['userId'] = $usersByEmail[$row['email']] ?? null;
                $row['userChoice'] = null !== $row['userId'] ? 'existing' : 'new';

                if ('new' === $row['userChoice']) {
                    $newLicenseeRowIndexesByEmail[$row['email']][] = \count($preview['rows']);
                }
            }

            $row['rowIndex'] = \count($preview['rows']);
            $preview['rows'][] = $row;
        }

        $this->linkSharedNewUserRows($preview['rows'], $newLicenseeRowIndexesByEmail);

        return $preview;
    }

    /**
     * When several new licensees share the same email address (typically a minor sharing
     * a parent/guardian's email), the account must be created for the adult and the
     * minor(s) linked to it, regardless of the order in which they appear in the CSV.
     *
     * @param list<array<string, int|string|bool|null>> $rows
     * @param array<string, list<int>> $rowIndexesByEmail
     */
    private function linkSharedNewUserRows(array &$rows, array $rowIndexesByEmail): void
    {
        foreach ($rowIndexesByEmail as $indexes) {
            if (\count($indexes) < 2) {
                continue;
            }

            $primaryIndex = $indexes[0];
            foreach ($indexes as $index) {
                if (LicenseCategoryType::ADULTES === $rows[$index]['category']) {
                    $primaryIndex = $index;
                    break;
                }
            }

            foreach ($indexes as $index) {
                if ($index !== $primaryIndex) {
                    $rows[$index]['sharedUserRow'] = $primaryIndex;
                }
            }

            $rows[$primaryIndex]['sharedUserDependentCount'] = \count($indexes) - 1;
        }
    }

    /**
     * @param array{line: int, fftaMemberCode: string, firstname: string, lastname: string, gender: string, birthdate: string, state: string, validFrom: string, type: string, ageCategory: string, email: string|null} $rawRow
     *
     * @return array<string, int|string|bool|null>
     */
    private function mapRow(array $rawRow): array
    {
        $birthdate = \DateTimeImmutable::createFromFormat('!d/m/Y', $rawRow['birthdate']);
        $validFrom = \DateTimeImmutable::createFromFormat('!d/m/Y', $rawRow['validFrom']);
        if (
            !$birthdate instanceof \DateTimeImmutable
            || !$validFrom instanceof \DateTimeImmutable
            || $birthdate->format('d/m/Y') !== $rawRow['birthdate']
            || $validFrom->format('d/m/Y') !== $rawRow['validFrom']
        ) {
            throw new \InvalidArgumentException('La date de naissance ou de validité est invalide.');
        }

        if (in_array('', [$rawRow['fftaMemberCode'], $rawRow['firstname'], $rawRow['lastname']], true)) {
            throw new \InvalidArgumentException('Le code adhérent, le prénom et le nom sont obligatoires.');
        }

        if (!preg_match('/^[A-Za-z0-9]{7,8}$/', $rawRow['fftaMemberCode'])) {
            throw new \InvalidArgumentException('Le code adhérent FFTA est invalide.');
        }

        if (null !== $rawRow['email'] && false === filter_var($rawRow['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L’adresse email est invalide.');
        }

        $gender = match ($rawRow['gender']) {
            'Masculin' => GenderType::MALE,
            'Féminin' => GenderType::FEMALE,
            default => throw new \InvalidArgumentException(\sprintf('Sexe FFTA inconnu : %s.', $rawRow['gender'])),
        };
        $type = match ($rawRow['type']) {
            'Adulte pratique en compétition' => LicenseType::ADULTES_COMPETITION,
            'Adulte pratique en club' => LicenseType::ADULTES_CLUB,
            'Jeune' => LicenseType::JEUNES,
            'U11' => LicenseType::POUSSINS,
            'Découverte' => LicenseType::DECOUVERTE,
            'Convention FFSU' => LicenseType::CONVENTION_FFSU,
            default => throw new \InvalidArgumentException(\sprintf('Type de licence FFTA inconnu : %s.', $rawRow['type'])),
        };
        [$category, $ageCategory] = $this->mapAgeCategory($rawRow['ageCategory']);

        return [
            'line' => $rawRow['line'],
            'fftaMemberCode' => strtoupper($rawRow['fftaMemberCode']),
            'firstname' => $rawRow['firstname'],
            'lastname' => $rawRow['lastname'],
            'gender' => $gender,
            'birthdate' => $birthdate->format('Y-m-d'),
            'season' => LicenseHelper::getSeasonForDate($validFrom),
            'type' => $type,
            'licenseTypeLabel' => LicenseType::getReadableValue($type),
            'category' => $category,
            'ageCategory' => $ageCategory,
            'activities' => LicenseActivityType::CL,
            'email' => $rawRow['email'],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function mapAgeCategory(string $ageCategory): array
    {
        return match ($ageCategory) {
            'U11' => [LicenseCategoryType::POUSSINS, LicenseAgeCategoryType::U11],
            'U13' => [LicenseCategoryType::JEUNES, LicenseAgeCategoryType::U13],
            'U15' => [LicenseCategoryType::JEUNES, LicenseAgeCategoryType::U15],
            'U18' => [LicenseCategoryType::JEUNES, LicenseAgeCategoryType::U18],
            'U21' => [LicenseCategoryType::JEUNES, LicenseAgeCategoryType::U21],
            'Sénior 1' => [LicenseCategoryType::ADULTES, LicenseAgeCategoryType::SENIOR_1],
            'Sénior 2' => [LicenseCategoryType::ADULTES, LicenseAgeCategoryType::SENIOR_2],
            'Sénior 3' => [LicenseCategoryType::ADULTES, LicenseAgeCategoryType::SENIOR_3],
            default => throw new \InvalidArgumentException(\sprintf('Catégorie d’âge FFTA inconnue : %s.', $ageCategory)),
        };
    }
}
