<?php

declare(strict_types=1);

namespace App\Tool;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:validate-migration',
    description: 'Validate the API Platform migration setup'
)]
class ValidateMigrationCommand extends Command
{
    private array $entities = [
        'User',
        'Club',
        'Licensee',
        'License',
        'Event',
        'ContestEvent',
        'TrainingEvent',
        'FreeTrainingEvent',
        'Applicant',
        'Arrow',
        'Bow',
        'SightAdjustment',
        'Group',
        'PracticeAdvice',
        'EventParticipation',
        'Result',
    ];

    private array $enums = [
        'GenderType',
        'DisciplineType',
        'LicenseActivityType',
        'LicenseAgeCategoryType',
        'ArrowType',
        'BowType',
        'FletchingType',
        'TargetTypeType',
        'ContestType',
        'EventParticipationStateType',
        'PracticeLevelType',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔍 Archery Manager Migration Validation');

        $errors = [];

        // Validate entities
        $io->section('📋 Validating Entities');
        foreach ($this->entities as $entity) {
            $className = "App\\Entity\\{$entity}";
            if (class_exists($className)) {
                $io->text("✅ {$entity}");
            } else {
                $errors[] = "❌ Missing entity: {$entity}";
                $io->text("❌ {$entity}");
            }
        }

        // Validate enums
        $io->section('🏷️  Validating Enums');
        foreach ($this->enums as $enum) {
            $className = "App\\Type\\{$enum}";
            if (enum_exists($className)) {
                $io->text("✅ {$enum}");
            } else {
                $errors[] = "❌ Missing enum: {$enum}";
                $io->text("❌ {$enum}");
            }
        }

        // Validate database schema
        $io->section('🗄️  Validating Database Schema');
        try {
            $connection = $this->entityManager->getConnection();
            $schemaManager = $connection->createSchemaManager();

            $expectedTables = [
                'users', 'clubs', 'licensees', 'licenses', 'events',
                'applicants', 'arrows', 'bows', 'sight_adjustments',
                'groups', 'practice_advices', 'event_participations', 'results',
            ];

            $existingTables = $schemaManager->listTableNames();

            foreach ($expectedTables as $table) {
                if (in_array($table, $existingTables)) {
                    $io->text("✅ Table: {$table}");
                } else {
                    $errors[] = "❌ Missing table: {$table}";
                    $io->text("❌ Table: {$table}");
                }
            }
        } catch (\Exception $e) {
            $errors[] = '❌ Database connection error: '.$e->getMessage();
            $io->error('Database connection failed: '.$e->getMessage());
        }

        // Validate API Platform annotations
        $io->section('🔗 Validating API Platform Setup');
        foreach ($this->entities as $entity) {
            $className = "App\\Entity\\{$entity}";
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                $attributes = $reflection->getAttributes();

                $hasApiResource = false;
                foreach ($attributes as $attribute) {
                    if (str_contains($attribute->getName(), 'ApiResource')) {
                        $hasApiResource = true;
                        break;
                    }
                }

                if ($hasApiResource) {
                    $io->text("✅ {$entity} has API Platform annotations");
                } else {
                    $errors[] = "❌ {$entity} missing API Platform annotations";
                    $io->text("❌ {$entity} missing API Platform annotations");
                }
            }
        }

        // Summary
        $io->section('📊 Validation Summary');

        if (empty($errors)) {
            $io->success('🎉 All validations passed! Your migration setup is ready.');

            $io->note([
                'Next steps:',
                '1. Run: php bin/console doctrine:migrations:migrate',
                '2. Customize: src/Tool/MigrateLegacyDataCommand.php',
                '3. Execute: php bin/console app:migrate-legacy-data',
                '4. Test your API endpoints!',
            ]);

            return Command::SUCCESS;
        } else {
            $io->error('❌ Validation failed with '.count($errors).' errors:');
            foreach ($errors as $error) {
                $io->text($error);
            }

            return Command::FAILURE;
        }
    }
}
