<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to create the new API Platform structure for Archery Manager
 */
final class Version20250808000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create new API Platform structure for Archery Manager';
    }

    public function up(Schema $schema): void
    {
        // Create applicants table
        $this->addSql('CREATE TABLE applicants (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            family_name VARCHAR(255) NOT NULL,
            given_name VARCHAR(255) NOT NULL,
            birth_date DATE NOT NULL,
            practice_level VARCHAR(50),
            license_number VARCHAR(7),
            phone_number VARCHAR(12),
            comment TEXT,
            season INTEGER NOT NULL,
            renewal BOOLEAN NOT NULL DEFAULT FALSE,
            license_type VARCHAR(32),
            on_waiting_list BOOLEAN NOT NULL DEFAULT FALSE,
            docs_retrieved BOOLEAN NOT NULL DEFAULT FALSE,
            paid BOOLEAN NOT NULL DEFAULT FALSE,
            license_created BOOLEAN NOT NULL DEFAULT FALSE,
            payment_observations VARCHAR(255),
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // Create arrows table
        $this->addSql('CREATE TABLE arrows (
            id SERIAL PRIMARY KEY,
            owner_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            spine INTEGER NOT NULL,
            fletching VARCHAR(50) NOT NULL,
            FOREIGN KEY (owner_id) REFERENCES licensees(id) ON DELETE CASCADE
        )');

        // Create bows table
        $this->addSql('CREATE TABLE bows (
            id SERIAL PRIMARY KEY,
            owner_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            brand VARCHAR(255),
            model VARCHAR(255),
            limb_size INTEGER,
            limb_strength INTEGER,
            brace_height DECIMAL(4,2),
            draw_length INTEGER,
            FOREIGN KEY (owner_id) REFERENCES licensees(id) ON DELETE CASCADE
        )');

        // Create sight_adjustments table
        $this->addSql('CREATE TABLE sight_adjustments (
            id SERIAL PRIMARY KEY,
            bow_id INTEGER NOT NULL,
            distance INTEGER NOT NULL,
            setting VARCHAR(255) NOT NULL,
            FOREIGN KEY (bow_id) REFERENCES bows(id) ON DELETE CASCADE
        )');

        // Create groups table
        $this->addSql('CREATE TABLE groups (
            id SERIAL PRIMARY KEY,
            club_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
        )');

        // Create group_licensees junction table
        $this->addSql('CREATE TABLE group_licensees (
            group_id INTEGER NOT NULL,
            licensee_id INTEGER NOT NULL,
            PRIMARY KEY (group_id, licensee_id),
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (licensee_id) REFERENCES licensees(id) ON DELETE CASCADE
        )');

        // Create practice_advices table
        $this->addSql('CREATE TABLE practice_advices (
            id SERIAL PRIMARY KEY,
            licensee_id INTEGER NOT NULL,
            author_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            advice TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            archived_at TIMESTAMP,
            FOREIGN KEY (licensee_id) REFERENCES licensees(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES licensees(id) ON DELETE CASCADE
        )');

        // Create results table
        $this->addSql('CREATE TABLE results (
            id SERIAL PRIMARY KEY,
            licensee_id INTEGER NOT NULL,
            event_id INTEGER NOT NULL,
            discipline VARCHAR(50) NOT NULL DEFAULT \'target\',
            age_category VARCHAR(10) NOT NULL DEFAULT \'S\',
            activity VARCHAR(10) NOT NULL DEFAULT \'CL\',
            distance INTEGER,
            target_type VARCHAR(50) NOT NULL DEFAULT \'monospot\',
            target_size INTEGER NOT NULL DEFAULT 122,
            total INTEGER NOT NULL DEFAULT 0,
            score1 INTEGER,
            score2 INTEGER,
            nb10 INTEGER,
            nb10p INTEGER,
            position INTEGER,
            ffta_ranking INTEGER,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (licensee_id) REFERENCES licensees(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        )');

        // Create event_participations table
        $this->addSql('CREATE TABLE event_participations (
            id SERIAL PRIMARY KEY,
            event_id INTEGER NOT NULL,
            participant_id INTEGER NOT NULL,
            activity VARCHAR(10),
            target_type VARCHAR(50),
            departure INTEGER,
            result_id INTEGER,
            participation_state VARCHAR(50) NOT NULL DEFAULT \'interested\',
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (participant_id) REFERENCES licensees(id) ON DELETE CASCADE,
            FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE SET NULL
        )');

        // Create event_groups junction table
        $this->addSql('CREATE TABLE event_groups (
            event_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL,
            PRIMARY KEY (event_id, group_id),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
        )');

        // Update events table to include new fields
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS discipline VARCHAR(50)');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS all_day BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS starts_at TIMESTAMP');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS ends_at TIMESTAMP');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS address VARCHAR(255)');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS slug VARCHAR(255)');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS latitude VARCHAR(16)');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS longitude VARCHAR(16)');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT \'other\'');

        // Create indexes for performance
        $this->addSql('CREATE INDEX idx_applicants_email ON applicants(email)');
        $this->addSql('CREATE INDEX idx_applicants_season ON applicants(season)');
        $this->addSql('CREATE INDEX idx_arrows_owner ON arrows(owner_id)');
        $this->addSql('CREATE INDEX idx_bows_owner ON bows(owner_id)');
        $this->addSql('CREATE INDEX idx_sight_adjustments_bow ON sight_adjustments(bow_id)');
        $this->addSql('CREATE INDEX idx_groups_club ON groups(club_id)');
        $this->addSql('CREATE INDEX idx_practice_advices_licensee ON practice_advices(licensee_id)');
        $this->addSql('CREATE INDEX idx_practice_advices_author ON practice_advices(author_id)');
        $this->addSql('CREATE INDEX idx_event_participations_event ON event_participations(event_id)');
        $this->addSql('CREATE INDEX idx_event_participations_participant ON event_participations(participant_id)');
        $this->addSql('CREATE INDEX idx_results_licensee ON results(licensee_id)');
        $this->addSql('CREATE INDEX idx_results_event ON results(event_id)');
        $this->addSql('CREATE INDEX idx_events_discipline ON events(discipline)');
        $this->addSql('CREATE INDEX idx_events_starts_at ON events(starts_at)');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order due to foreign key constraints
        $this->addSql('DROP TABLE IF EXISTS event_groups');
        $this->addSql('DROP TABLE IF EXISTS results');
        $this->addSql('DROP TABLE IF EXISTS event_participations');
        $this->addSql('DROP TABLE IF EXISTS practice_advices');
        $this->addSql('DROP TABLE IF EXISTS group_licensees');
        $this->addSql('DROP TABLE IF EXISTS groups');
        $this->addSql('DROP TABLE IF EXISTS sight_adjustments');
        $this->addSql('DROP TABLE IF EXISTS bows');
        $this->addSql('DROP TABLE IF EXISTS arrows');
        $this->addSql('DROP TABLE IF EXISTS applicants');

        // Remove added columns from events table
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS discipline');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS all_day');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS starts_at');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS ends_at');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS address');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS slug');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS latitude');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS longitude');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS updated_at');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS type');
    }
}
