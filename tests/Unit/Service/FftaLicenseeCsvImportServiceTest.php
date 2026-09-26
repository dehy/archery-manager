<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Exception\FftaLicenseeCsvImportException;
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

    public function testRejectsEmptyCsvAndMissingHeaders(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        try {
            $service->createPreview($this->csvFile(''));
            self::fail('An empty CSV should be rejected.');
        } catch (FftaLicenseeCsvImportException $fftaLicenseeCsvImportException) {
            $this->assertSame('Le fichier CSV est vide.', $fftaLicenseeCsvImportException->getMessage());
        }

        $this->expectException(FftaLicenseeCsvImportException::class);
        $this->expectExceptionMessage('Colonnes CSV manquantes');
        $service->createPreview($this->csvFile("\"Code Adhérent\";\"Nom\"\n"));
    }

    public function testReportsAllRowValidationErrorsAndDuplicates(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        $preview = $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"1234567A";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"1234567B";"Dupont";"Camille";"Féminin";"invalid";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"1234567C";"";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"bad";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"1234567D";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"invalid-email"'."\n"
            .'"1234567E";"Dupont";"Camille";"Inconnu";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";"camille@example.test"'."\n"
            .'"1234567F";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Inconnu";"U18";"camille@example.test"'."\n"
            .'"1234567G";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"Inconnu";"camille@example.test"'."\n"
            .'"1234567H";"Dupont";"Camille";"Féminin";"12/05/2010";"Active";"01/09/2026";"Jeune";"U18";""'."\n",
        ));

        $this->assertCount(1, $preview['rows']);
        $this->assertCount(9, $preview['errors']);
    }

    public function testMapsSupportedLicenseAndAgeCategories(): void
    {
        $service = new FftaLicenseeCsvImportService(
            $this->createStub(LicenseeRepository::class),
            $this->createStub(UserRepository::class),
        );

        $preview = $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"U11";"One";"Masculin";"12/05/2018";"Active";"01/09/2026";"U11";"U11";"u11@example.test"'."\n"
            .'"1234567B";"Senior";"One";"Féminin";"12/05/1998";"Active";"01/09/2026";"Adulte pratique en compétition";"Sénior 1";"s1@example.test"'."\n"
            .'"1234567C";"Senior";"Two";"Masculin";"12/05/1988";"Active";"01/09/2026";"Découverte";"Sénior 2";"s2@example.test"'."\n"
            .'"1234567D";"Senior";"Three";"Féminin";"12/05/1978";"Active";"01/09/2026";"Convention FFSU";"Sénior 3";"s3@example.test"'."\n"
            .'"1234567E";"Young";"Adult";"Masculin";"12/05/2006";"Active";"01/09/2026";"Jeune";"U21";"u21@example.test"'."\n",
        ));

        $this->assertCount(5, $preview['rows']);
        $this->assertSame(LicenseType::POUSSINS, $preview['rows'][0]['type']);
        $this->assertSame(LicenseType::ADULTES_COMPETITION, $preview['rows'][1]['type']);
        $this->assertSame(LicenseType::DECOUVERTE, $preview['rows'][2]['type']);
        $this->assertSame(LicenseType::CONVENTION_FFSU, $preview['rows'][3]['type']);
        $this->assertSame(LicenseAgeCategoryType::U21, $preview['rows'][4]['ageCategory']);
    }

    public function testReconcilesExistingAndAlreadyLicensedLicensees(): void
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(42);

        $renewal = $this->createStub(Licensee::class);
        $renewal->method('getId')->willReturn(10);
        $renewal->method('getFftaMemberCode')->willReturn('1234567A');
        $renewal->method('getUser')->willReturn($user);
        $renewal->method('getLicenseForSeason')->willReturn(null);

        $alreadyLicensed = $this->createStub(Licensee::class);
        $alreadyLicensed->method('getId')->willReturn(11);
        $alreadyLicensed->method('getFftaMemberCode')->willReturn('1234567B');
        $alreadyLicensed->method('getLicenseForSeason')->willReturn($this->createStub(License::class));

        $licenseeRepository = $this->createStub(LicenseeRepository::class);
        $licenseeRepository->method('findByCodesWithLicenses')->willReturn([$renewal, $alreadyLicensed]);

        $service = new FftaLicenseeCsvImportService($licenseeRepository, $this->createStub(UserRepository::class));
        $preview = $service->createPreview($this->csvFile(
            self::HEADERS
            .'"1234567A";"Renewal";"One";"Féminin";"12/05/1990";"Active";"01/09/2026";"Adulte pratique en club";"Sénior 1";"renewal@example.test"'."\n"
            .'"1234567B";"Licensed";"One";"Masculin";"12/05/1990";"Active";"01/09/2026";"Adulte pratique en club";"Sénior 1";"licensed@example.test"'."\n",
        ));

        $this->assertCount(1, $preview['rows']);
        $this->assertSame('existing-licensee', $preview['rows'][0]['userChoice']);
        $this->assertSame(42, $preview['rows'][0]['userId']);
        $this->assertCount(1, $preview['alreadyLicensed']);
        $this->assertTrue($preview['alreadyLicensed'][0]['alreadyLicensed']);
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
