<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Service\FftaLicenseeCsvImportService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FftaLicenseeCsvImportServiceTest extends TestCase
{
    private const string HEADERS = "\xEF\xBB\xBF\"Code Adhérent\";\"Nom\";\"Prénom\";\"Sexe\";\"Date de naissance\";\"État\";\"Valide depuis le\";\"Type\";\"Catégorie âge\";\"Email\"\n";

    public function testCreatesPreviewAndDerivesSeasonFromValidityDate(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        $preview = $service->createPreview($this->csvFile(
            self::HEADERS.'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n",
        ));

        $this->assertCount(1, $preview['rows']);
        $this->assertSame(2027, $preview['rows'][0]['season']);
        $this->assertSame(LicenseType::JEUNES, $preview['rows'][0]['type']);
        $this->assertSame(LicenseCategoryType::JEUNES, $preview['rows'][0]['category']);
        $this->assertSame(LicenseAgeCategoryType::U18, $preview['rows'][0]['ageCategory']);
        $this->assertSame(LicenseActivityType::CL, $preview['rows'][0]['activities']);
    }

    public function testSkipsInactiveRowsAndReportsInvalidRows(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        $preview = $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2010";"Inactive";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"1234567B";"Martin";"Alex";"Inconnu";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"alex@example.test"'."\n",
        ));

        $this->assertSame([], $preview['rows']);
        $this->assertCount(1, $preview['skipped']);
        $this->assertCount(1, $preview['errors']);
    }

    public function testLinksNewMinorLicenseesToTheAdultSharingTheirEmailRegardlessOfCsvOrder(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        // The child is listed before the parent in the CSV: the adult must still be
        // selected as the primary row (the one that gets a new User account created).
        $preview = $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2015";"Active";"01/09/2026";"Jeune";"U13";"famille@example.test"'."\n"
            .'"1234567B";"Dupont";"Léa";"Féminin";"12/05/2012";"Active";"01/09/2026";"Jeune";"U18";"famille@example.test"'."\n"
            .'"1234567C";"Dupont";"Alex";"Masculin";"12/05/1985";"Active";"01/09/2026";"Adulte pratique en club";"Sénior 1";"famille@example.test"'."\n",
        ));

        $this->assertCount(3, $preview['rows']);
        $childOne = $preview['rows'][0];
        $childTwo = $preview['rows'][1];
        $adult = $preview['rows'][2];

        $this->assertSame(LicenseCategoryType::ADULTES, $adult['category']);
        $this->assertSame('new', $adult['userChoice']);
        $this->assertArrayNotHasKey('sharedUserRow', $adult);
        $this->assertSame(2, $adult['sharedUserDependentCount']);

        $this->assertSame('new', $childOne['userChoice']);
        $this->assertSame(2, $childOne['sharedUserRow']);
        $this->assertSame('new', $childTwo['userChoice']);
        $this->assertSame(2, $childTwo['sharedUserRow']);
    }

    public function testFallsBackToFirstRowWhenNoAdultSharesTheEmail(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        $preview = $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2015";"Active";"01/09/2026";"Jeune";"U13";"fratrie@example.test"'."\n"
            .'"1234567B";"Dupont";"Léa";"Féminin";"12/05/2012";"Active";"01/09/2026";"Jeune";"U18";"fratrie@example.test"'."\n",
        ));

        $this->assertCount(2, $preview['rows']);
        $this->assertArrayNotHasKey('sharedUserRow', $preview['rows'][0]);
        $this->assertSame(1, $preview['rows'][0]['sharedUserDependentCount']);
        $this->assertSame(0, $preview['rows'][1]['sharedUserRow']);
    }

    public function testLoadsLicenseesAndUsersInBulk(): void
    {
        $licenseeRepository = $this->createMock(LicenseeRepository::class);
        $licenseeRepository->expects($this->once())
            ->method('findByCodesWithLicenses')
            ->with(['1234567A', '1234567B'])
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findByEmails')
            ->with(['one@example.test', 'two@example.test'])
            ->willReturn([]);

        $service = new FftaLicenseeCsvImportService($licenseeRepository, $userRepository);
        $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"one@example.test"'."\n"
            .'"1234567B";"Martin";"Alex";"Masculin";"12/05/1985";"Active";"01/09/2026";"Adulte pratique en club";"Sénior 1";"two@example.test"'."\n",
        ));
    }

    private function csvFile(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ffta-licensees-');
        if (false === $path) {
            self::fail('Unable to create temporary CSV file.');
        }

        file_put_contents($path, $contents);

        return new UploadedFile($path, 'licensees.csv', 'text/csv', null, true);
    }
}
