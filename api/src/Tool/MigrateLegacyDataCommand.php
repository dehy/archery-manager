<?php

declare(strict_types=1);

namespace App\Tool;

use App\Entity\Applicant as NewApplicant;
use App\Entity\Arrow as NewArrow;
use App\Entity\Bow as NewBow;
use App\Entity\EventParticipation as NewEventParticipation;
use App\Entity\Group as NewGroup;
use App\Entity\PracticeAdvice as NewPracticeAdvice;
use App\Entity\Result as NewResult;
use App\Type\ArrowType;
use App\Type\BowType;
use App\Type\ContestType;
use App\Type\DisciplineType;
use App\Type\EventParticipationStateType;
use App\Type\FletchingType;
use App\Type\LicenseActivityType;
use App\Type\LicenseAgeCategoryType;
use App\Type\PracticeLevelType;
use App\Type\TargetTypeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-legacy-data',
    description: 'Migrate data from legacy database structure to new API Platform structure'
)]
class MigrateLegacyDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🏹 Archery Manager Legacy Data Migration');
        
        try {
            $this->migrateApplicants($io);
            $this->migrateArrows($io);
            $this->migrateBows($io);
            $this->migrateGroups($io);
            $this->migratePracticeAdvices($io);
            $this->migrateEventParticipations($io);
            $this->migrateResults($io);
            
            $io->success('✅ All legacy data has been successfully migrated!');
            
        } catch (\Exception $e) {
            $io->error('❌ Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }

    private function migrateApplicants(SymfonyStyle $io): void
    {
        $io->section('📝 Migrating Applicants');
        
        // This would typically fetch from your legacy database
        // You'll need to adapt this to your specific legacy data source
        $legacyApplicants = $this->fetchLegacyData('applicant');
        
        $io->progressStart(count($legacyApplicants));
        
        foreach ($legacyApplicants as $legacy) {
            $applicant = new NewApplicant();
            $applicant->email = $legacy['email'];
            $applicant->familyName = $legacy['lastname'];
            $applicant->givenName = $legacy['firstname'];
            $applicant->birthDate = new \DateTimeImmutable($legacy['birthdate']);
            $applicant->practiceLevel = PracticeLevelType::tryFrom($legacy['practice_level']);
            $applicant->licenseNumber = $legacy['license_number'];
            $applicant->phoneNumber = $legacy['phone_number'];
            $applicant->comment = $legacy['comment'];
            $applicant->season = (int) $legacy['season'];
            $applicant->renewal = (bool) $legacy['renewal'];
            $applicant->licenseType = $legacy['license_type'];
            $applicant->onWaitingList = (bool) $legacy['on_waiting_list'];
            $applicant->docsRetrieved = (bool) $legacy['docs_retrieved'] ?? false;
            $applicant->paid = (bool) $legacy['paid'] ?? false;
            $applicant->licenseCreated = (bool) $legacy['license_created'] ?? false;
            $applicant->paymentObservations = $legacy['payment_observations'];
            
            $this->entityManager->persist($applicant);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Applicants migrated successfully');
    }

    private function migrateArrows(SymfonyStyle $io): void
    {
        $io->section('🏹 Migrating Arrows');
        
        $legacyArrows = $this->fetchLegacyData('arrow');
        
        $io->progressStart(count($legacyArrows));
        
        foreach ($legacyArrows as $legacy) {
            $arrow = new NewArrow();
            // You'll need to map the owner_id to the new Licensee entity
            // $arrow->owner = $this->findLicenseeById($legacy['owner_id']);
            $arrow->type = ArrowType::tryFrom($legacy['type']) ?? ArrowType::Carbon;
            $arrow->spine = (int) $legacy['spine'];
            $arrow->fletching = FletchingType::tryFrom($legacy['fletching']) ?? FletchingType::Plastic;
            
            $this->entityManager->persist($arrow);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Arrows migrated successfully');
    }

    private function migrateBows(SymfonyStyle $io): void
    {
        $io->section('🏹 Migrating Bows');
        
        $legacyBows = $this->fetchLegacyData('bow');
        
        $io->progressStart(count($legacyBows));
        
        foreach ($legacyBows as $legacy) {
            $bow = new NewBow();
            // $bow->owner = $this->findLicenseeById($legacy['owner_id']);
            $bow->type = BowType::tryFrom($legacy['type']) ?? BowType::Recurve;
            $bow->brand = $legacy['brand'];
            $bow->model = $legacy['model'];
            $bow->limbSize = $legacy['limb_size'] ? (int) $legacy['limb_size'] : null;
            $bow->limbStrength = $legacy['limb_strength'] ? (int) $legacy['limb_strength'] : null;
            $bow->braceHeight = $legacy['brace_height'] ? (float) $legacy['brace_height'] : null;
            $bow->drawLength = $legacy['draw_length'] ? (int) $legacy['draw_length'] : null;
            
            $this->entityManager->persist($bow);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Bows migrated successfully');
    }

    private function migrateGroups(SymfonyStyle $io): void
    {
        $io->section('👥 Migrating Groups');
        
        $legacyGroups = $this->fetchLegacyData('group');
        
        $io->progressStart(count($legacyGroups));
        
        foreach ($legacyGroups as $legacy) {
            $group = new NewGroup();
            // $group->club = $this->findClubById($legacy['club_id']);
            $group->name = $legacy['name'];
            $group->description = $legacy['description'];
            
            $this->entityManager->persist($group);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Groups migrated successfully');
    }

    private function migratePracticeAdvices(SymfonyStyle $io): void
    {
        $io->section('💡 Migrating Practice Advices');
        
        $legacyAdvices = $this->fetchLegacyData('practice_advice');
        
        $io->progressStart(count($legacyAdvices));
        
        foreach ($legacyAdvices as $legacy) {
            $advice = new NewPracticeAdvice();
            // $advice->licensee = $this->findLicenseeById($legacy['licensee_id']);
            // $advice->author = $this->findLicenseeById($legacy['author_id']);
            $advice->title = $legacy['title'];
            $advice->advice = $legacy['advice'];
            $advice->createdAt = new \DateTimeImmutable($legacy['created_at']);
            $advice->archivedAt = $legacy['archived_at'] ? new \DateTimeImmutable($legacy['archived_at']) : null;
            
            $this->entityManager->persist($advice);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Practice Advices migrated successfully');
    }

    private function migrateEventParticipations(SymfonyStyle $io): void
    {
        $io->section('🎯 Migrating Event Participations');
        
        $legacyParticipations = $this->fetchLegacyData('event_participation');
        
        $io->progressStart(count($legacyParticipations));
        
        foreach ($legacyParticipations as $legacy) {
            $participation = new NewEventParticipation();
            // $participation->event = $this->findEventById($legacy['event_id']);
            // $participation->participant = $this->findLicenseeById($legacy['participant_id']);
            $participation->activity = LicenseActivityType::tryFrom($legacy['activity']);
            $participation->targetType = TargetTypeType::tryFrom($legacy['target_type']);
            $participation->departure = $legacy['departure'] ? (int) $legacy['departure'] : null;
            $participation->participationState = EventParticipationStateType::tryFrom($legacy['participation_state']) ?? EventParticipationStateType::Interested;
            
            $this->entityManager->persist($participation);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Event Participations migrated successfully');
    }

    private function migrateResults(SymfonyStyle $io): void
    {
        $io->section('🏆 Migrating Results');
        
        $legacyResults = $this->fetchLegacyData('result');
        
        $io->progressStart(count($legacyResults));
        
        foreach ($legacyResults as $legacy) {
            $result = new NewResult();
            // $result->licensee = $this->findLicenseeById($legacy['licensee_id']);
            // $result->event = $this->findEventById($legacy['event_id']);
            $result->discipline = DisciplineType::tryFrom($legacy['discipline']) ?? DisciplineType::Target;
            $result->ageCategory = LicenseAgeCategoryType::tryFrom($legacy['age_category']) ?? LicenseAgeCategoryType::SENIOR;
            $result->activity = LicenseActivityType::tryFrom($legacy['activity']) ?? LicenseActivityType::CL;
            $result->distance = $legacy['distance'] ? (int) $legacy['distance'] : null;
            $result->targetType = TargetTypeType::tryFrom($legacy['target_type']) ?? TargetTypeType::Monospot;
            $result->targetSize = (int) $legacy['target_size'];
            $result->total = (int) $legacy['total'];
            $result->score1 = $legacy['score1'] ? (int) $legacy['score1'] : null;
            $result->score2 = $legacy['score2'] ? (int) $legacy['score2'] : null;
            $result->nb10 = $legacy['nb10'] ? (int) $legacy['nb10'] : null;
            $result->nb10p = $legacy['nb10p'] ? (int) $legacy['nb10p'] : null;
            $result->position = $legacy['position'] ? (int) $legacy['position'] : null;
            $result->fftaRanking = $legacy['ffta_ranking'] ? (int) $legacy['ffta_ranking'] : null;
            
            $this->entityManager->persist($result);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        $io->text('✅ Results migrated successfully');
    }

    /**
     * This method should be implemented to fetch data from your legacy database
     * You can either:
     * 1. Connect to the legacy database directly
     * 2. Read from exported JSON/CSV files
     * 3. Use any other data source
     */
    private function fetchLegacyData(string $entityType): array
    {
        // TODO: Implement this method based on your legacy data source
        // For example:
        // return $this->legacyDatabase->fetchAll("SELECT * FROM {$entityType}");
        
        return []; // Placeholder - replace with actual data fetching
    }
}
