<?php

declare(strict_types=1);

namespace App\Service;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\Licensee;
use App\Exception\FftaLicenseeCsvImportException;
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
            throw new FftaLicenseeCsvImportException('Impossible de lire le fichier CSV importé.');
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
                throw new FftaLicenseeCsvImportException('Le fichier CSV est vide.');
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
            throw new FftaLicenseeCsvImportException(\sprintf('Colonnes CSV manquantes : %s.', implode(', ', $missingHeaders)));
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
        foreach ($this->licenseeRepository->findByCodesWithLicenses($codes) as $licensee) {
            $licenseesByCode[$licensee->getFftaMemberCode()] = $licensee;
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
        foreach ($this->userRepository->findByEmails($emails) as $user) {
            $email = strtolower((string) $user->getEmail());
            if (null !== $user->getId()) {
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
        /** @var array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview */
        $preview = ['rows' => [], 'alreadyLicensed' => [], 'skipped' => [], 'errors' => []];
        /** @var array<string, list<int>> $newLicenseeRowIndexesByEmail */
        $newLicenseeRowIndexesByEmail = [];
        /** @var array<string, int> $seenMemberCodes */
        $seenMemberCodes = [];

        foreach ($rawRows as $rawRow) {
            $row = $this->validatedActiveRow($rawRow, $preview, $seenMemberCodes);
            if (null === $row) {
                continue;
            }

            $licensee = $licenseesByCode[$row['fftaMemberCode']] ?? null;
            $row = $this->reconcileRow($row, $licensee, $usersByEmail, $preview, $newLicenseeRowIndexesByEmail);
            if (null === $row) {
                continue;
            }

            $row['rowIndex'] = \count($preview['rows']);
            $preview['rows'][] = $row;
        }

        $this->linkSharedNewUserRows($preview['rows'], $newLicenseeRowIndexesByEmail);

        return $preview;
    }

    /**
     * @param array{line: int, fftaMemberCode: string, firstname: string, lastname: string, gender: string, birthdate: string, state: string, validFrom: string, type: string, ageCategory: string, email: string|null} $rawRow
     * @param array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param array<string, int> $seenMemberCodes
     * @param-out array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param-out array<string, int> $seenMemberCodes
     *
     * @return array<string, int|string|bool|null>|null
     */
    private function validatedActiveRow(array $rawRow, array &$preview, array &$seenMemberCodes): ?array
    {
        $row = $this->mapActiveRow($rawRow, $preview);
        if (null === $row) {
            return null;
        }

        $fftaMemberCode = (string) $row['fftaMemberCode'];
        if (isset($seenMemberCodes[$fftaMemberCode])) {
            $preview['errors'][] = ['line' => (int) $row['line'], 'reason' => \sprintf('Le code adhérent est déjà présent à la ligne %d.', $seenMemberCodes[$fftaMemberCode])];

            return null;
        }

        $seenMemberCodes[$fftaMemberCode] = (int) $row['line'];

        return $row;
    }

    /**
     * @param array{line: int, fftaMemberCode: string, firstname: string, lastname: string, gender: string, birthdate: string, state: string, validFrom: string, type: string, ageCategory: string, email: string|null} $rawRow
     * @param array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param-out array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     *
     * @return array<string, int|string|bool|null>|null
     */
    private function mapActiveRow(array $rawRow, array &$preview): ?array
    {
        if (self::STATE_ACTIVE !== $rawRow['state']) {
            $preview['skipped'][] = ['line' => $rawRow['line'], 'reason' => \sprintf('État FFTA « %s » non importé.', $rawRow['state'])];

            return null;
        }

        try {
            return $this->mapRow($rawRow);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $preview['errors'][] = ['line' => $rawRow['line'], 'reason' => $invalidArgumentException->getMessage()];

            return null;
        }
    }

    /**
     * @param array<string, int|string|bool|null> $row
     * @param array<string, int> $usersByEmail
     * @param array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param array<string, list<int>> $newLicenseeRowIndexesByEmail
     * @param-out array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param-out array<string, list<int>> $newLicenseeRowIndexesByEmail
     *
     * @return array<string, int|string|bool|null>|null
     */
    private function reconcileRow(array $row, ?Licensee $licensee, array $usersByEmail, array &$preview, array &$newLicenseeRowIndexesByEmail): ?array
    {
        if ($licensee instanceof Licensee) {
            return $this->reconcileExistingLicensee($row, $licensee, $preview);
        }

        return $this->reconcileNewLicensee($row, $usersByEmail, $preview, $newLicenseeRowIndexesByEmail);
    }

    /**
     * @param array<string, int|string|bool|null> $row
     * @param array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param-out array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     *
     * @return array<string, int|string|bool|null>|null
     */
    private function reconcileExistingLicensee(array $row, Licensee $licensee, array &$preview): ?array
    {
        if ($licensee->getLicenseForSeason((int) $row['season']) instanceof \App\Entity\License) {
            $row['licenseeId'] = $licensee->getId();
            $row['alreadyLicensed'] = true;
            $preview['alreadyLicensed'][] = $row;

            return null;
        }

        $row['licenseeId'] = $licensee->getId();
        $row['userId'] = $licensee->getUser()?->getId();
        $row['userChoice'] = 'existing-licensee';

        return $row;
    }

    /**
     * @param array<string, int|string|bool|null> $row
     * @param array<string, int> $usersByEmail
     * @param array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param array<string, list<int>> $newLicenseeRowIndexesByEmail
     * @param-out array{rows: list<array<string, int|string|bool|null>>, alreadyLicensed: list<array<string, int|string|bool|null>>, skipped: list<array{line: int, reason: string}>, errors: list<array{line: int, reason: string}>} $preview
     * @param-out array<string, list<int>> $newLicenseeRowIndexesByEmail
     *
     * @return array<string, int|string|bool|null>|null
     */
    private function reconcileNewLicensee(array $row, array $usersByEmail, array &$preview, array &$newLicenseeRowIndexesByEmail): ?array
    {
        if (null === $row['email']) {
            $preview['errors'][] = ['line' => (int) $row['line'], 'reason' => 'Une adresse email est requise pour créer un nouveau licencié.'];

            return null;
        }

        $row['licenseeId'] = null;
        $email = (string) $row['email'];
        $row['userId'] = $usersByEmail[$email] ?? null;
        $row['userChoice'] = null !== $row['userId'] ? 'existing' : 'new';

        if ('new' === $row['userChoice']) {
            $newLicenseeRowIndexesByEmail[$email][] = \count($preview['rows']);
        }

        return $row;
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
