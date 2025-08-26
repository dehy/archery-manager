<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826203201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create complete archery manager database schema with database-agnostic approach';
    }

    public function up(Schema $schema): void
    {
        // Database-agnostic approach for ID columns and timestamps
        $platform = $this->connection->getDatabasePlatform();
        $isPostgreSQL = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        $idType = $isPostgreSQL ? 'SERIAL' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $primaryKeyClause = $isPostgreSQL ? ', PRIMARY KEY (id)' : '';
        $timestampType = $isPostgreSQL ? 'TIMESTAMP(0) WITHOUT TIME ZONE' : 'DATETIME';
        $booleanType = $isPostgreSQL ? 'BOOLEAN' : 'BOOLEAN';
        $jsonType = $isPostgreSQL ? 'JSON' : 'TEXT';
        
        // Foreign key constraints - inline for SQLite, separate for PostgreSQL
        $eventIdFK = $isPostgreSQL ? 'event_id INT NOT NULL' : 'event_id INT NOT NULL REFERENCES events(id)';
        $participantIdFK = $isPostgreSQL ? 'participant_id INT NOT NULL' : 'participant_id INT NOT NULL REFERENCES licensees(id)';
        $resultIdFK = $isPostgreSQL ? 'result_id INT DEFAULT NULL' : 'result_id INT DEFAULT NULL REFERENCES results(id)';
        $licenseeIdFK = $isPostgreSQL ? 'licensee_id INT DEFAULT NULL' : 'licensee_id INT DEFAULT NULL REFERENCES licensees(id)';
        $clubIdFK = $isPostgreSQL ? 'club_id INT DEFAULT NULL' : 'club_id INT DEFAULT NULL REFERENCES clubs(id)';
        $clubIdNotNullFK = $isPostgreSQL ? 'club_id INT NOT NULL' : 'club_id INT NOT NULL REFERENCES clubs(id)';
        $userIdFK = $isPostgreSQL ? 'user_id INT DEFAULT NULL' : 'user_id INT DEFAULT NULL REFERENCES users(id)';
        $licenseeIdNotNullFK = $isPostgreSQL ? 'licensee_id INT NOT NULL' : 'licensee_id INT NOT NULL REFERENCES licensees(id)';
        
        // Create tables with database-agnostic ID types
        $this->addSql("CREATE TABLE clubs (id $idType NOT NULL, sport VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, logo_name VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, email VARCHAR(255) NOT NULL, ffta_code VARCHAR(7) NOT NULL, ffta_username VARCHAR(255) DEFAULT NULL, ffta_password VARCHAR(255) DEFAULT NULL, created_at $timestampType NOT NULL, updated_at $timestampType NOT NULL, address_country VARCHAR(255) DEFAULT NULL, address_locality VARCHAR(255) DEFAULT NULL, address_postal_code VARCHAR(255) DEFAULT NULL, address_address VARCHAR(255) DEFAULT NULL$primaryKeyClause)");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5BD31235E237E06 ON clubs (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5BD3123E869DAE5 ON clubs (ffta_code)');
        $this->addSql("CREATE TABLE event_participations (id $idType NOT NULL, activity VARCHAR(255) DEFAULT NULL, target_type VARCHAR(255) DEFAULT NULL, departure INT DEFAULT NULL, participation_state VARCHAR(255) NOT NULL, comment TEXT DEFAULT NULL, registration_date $timestampType DEFAULT NULL, $eventIdFK, $participantIdFK, $resultIdFK, $licenseeIdFK$primaryKeyClause)");
        $this->addSql('CREATE INDEX IDX_2282709B71F7E88B ON event_participations (event_id)');
        $this->addSql('CREATE INDEX IDX_2282709B9D1C3019 ON event_participations (participant_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2282709B7A7B643 ON event_participations (result_id)');
        $this->addSql('CREATE INDEX IDX_2282709B734B22EE ON event_participations (licensee_id)');
        $this->addSql("CREATE TABLE event_participations_audit (id $idType NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs $jsonType DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at $timestampType NOT NULL$primaryKeyClause)");
        $this->addSql('CREATE INDEX type_438b3e3cf6c792c24d39906b2e34d45d_idx ON event_participations_audit (type)');
        $this->addSql('CREATE INDEX object_id_438b3e3cf6c792c24d39906b2e34d45d_idx ON event_participations_audit (object_id)');
        $this->addSql('CREATE INDEX discriminator_438b3e3cf6c792c24d39906b2e34d45d_idx ON event_participations_audit (discriminator)');
        $this->addSql('CREATE INDEX transaction_hash_438b3e3cf6c792c24d39906b2e34d45d_idx ON event_participations_audit (transaction_hash)');
        $this->addSql('CREATE INDEX blame_id_438b3e3cf6c792c24d39906b2e34d45d_idx ON event_participations_audit (blame_id)');
        $this->addSql('CREATE INDEX created_at_438b3e3cf6c792c24d39906b2e34d45d_idx ON event_participations_audit (created_at)');
        $this->addSql("CREATE TABLE events (id $idType NOT NULL, name VARCHAR(255) NOT NULL, discipline VARCHAR(255) DEFAULT NULL, all_day $booleanType NOT NULL, starts_at $timestampType NOT NULL, ends_at $timestampType NOT NULL, max_participants INT DEFAULT NULL, slug VARCHAR(255) NOT NULL, latitude VARCHAR(16) DEFAULT NULL, longitude VARCHAR(16) DEFAULT NULL, updated_at $timestampType NOT NULL, status VARCHAR(255) NOT NULL, start_date $timestampType NOT NULL, address_country VARCHAR(255) DEFAULT NULL, address_locality VARCHAR(255) DEFAULT NULL, address_postal_code VARCHAR(255) DEFAULT NULL, address_address VARCHAR(255) DEFAULT NULL, $clubIdFK, type VARCHAR(255) NOT NULL, contest_type VARCHAR(255) DEFAULT NULL$primaryKeyClause)");
        $this->addSql('CREATE INDEX IDX_5387574A61190A32 ON events (club_id)');
        
        // Create junction table variables
        $eventIdJunctionFK = $isPostgreSQL ? 'event_id INT NOT NULL' : 'event_id INT NOT NULL REFERENCES events(id) ON DELETE CASCADE';
        $groupIdJunctionFK = $isPostgreSQL ? 'group_id INT NOT NULL' : 'group_id INT NOT NULL REFERENCES groups(id) ON DELETE CASCADE';
        $licenseeIdJunctionFK = $isPostgreSQL ? 'licensee_id INT NOT NULL' : 'licensee_id INT NOT NULL REFERENCES licensees(id) ON DELETE CASCADE';
        
        $this->addSql("CREATE TABLE event_groups ($eventIdJunctionFK, $groupIdJunctionFK, PRIMARY KEY (event_id, group_id))");
        $this->addSql('CREATE INDEX IDX_C2F44E2271F7E88B ON event_groups (event_id)');
        $this->addSql('CREATE INDEX IDX_C2F44E22FE54D947 ON event_groups (group_id)');
        $this->addSql("CREATE TABLE groups (id $idType NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, $clubIdNotNullFK$primaryKeyClause)");
        $this->addSql('CREATE INDEX IDX_F06D397061190A32 ON groups (club_id)');
        
        $this->addSql("CREATE TABLE group_licensees ($groupIdJunctionFK, $licenseeIdJunctionFK, PRIMARY KEY (group_id, licensee_id))");
        $this->addSql('CREATE INDEX IDX_713C390FFE54D947 ON group_licensees (group_id)');
        $this->addSql('CREATE INDEX IDX_713C390F734B22EE ON group_licensees (licensee_id)');
        $this->addSql("CREATE TABLE licensees (id $idType NOT NULL, gender VARCHAR(255) NOT NULL, family_name VARCHAR(255) NOT NULL, given_name VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, ffta_member_code VARCHAR(8) DEFAULT NULL, ffta_id INT DEFAULT NULL, updated_at $timestampType NOT NULL, $userIdFK$primaryKeyClause)");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B18F65EE68FC69F7 ON licensees (ffta_member_code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B18F65EEBD6F80AD ON licensees (ffta_id)');
        $this->addSql('CREATE INDEX IDX_B18F65EEA76ED395 ON licensees (user_id)');
        $this->addSql("CREATE TABLE licensees_audit (id $idType NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs $jsonType DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at $timestampType NOT NULL$primaryKeyClause)");
        $this->addSql('CREATE INDEX type_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (type)');
        $this->addSql('CREATE INDEX object_id_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (object_id)');
        $this->addSql('CREATE INDEX discriminator_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (discriminator)');
        $this->addSql('CREATE INDEX transaction_hash_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (transaction_hash)');
        $this->addSql('CREATE INDEX blame_id_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (blame_id)');
        $this->addSql('CREATE INDEX created_at_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (created_at)');
        $this->addSql("CREATE TABLE licenses (id $idType NOT NULL, season INT NOT NULL, type VARCHAR(255) NOT NULL, category VARCHAR(255) DEFAULT NULL, age_category VARCHAR(255) DEFAULT NULL, activities TEXT NOT NULL, $licenseeIdNotNullFK, $clubIdNotNullFK$primaryKeyClause)");
        $this->addSql('CREATE INDEX IDX_7F320F3F734B22EE ON licenses (licensee_id)');
        $this->addSql('CREATE INDEX IDX_7F320F3F61190A32 ON licenses (club_id)');
        // Create additional variables for results table
        $eventIdNotNullFK = $isPostgreSQL ? 'event_id INT NOT NULL' : 'event_id INT NOT NULL REFERENCES events(id)';
        
        $this->addSql("CREATE TABLE results (id $idType NOT NULL, discipline VARCHAR(255) NOT NULL, age_category VARCHAR(255) NOT NULL, activity VARCHAR(255) NOT NULL, distance INT DEFAULT NULL, target_type VARCHAR(255) NOT NULL, target_size INT NOT NULL, total INT NOT NULL, score1 INT DEFAULT NULL, score2 INT DEFAULT NULL, nb10 INT DEFAULT NULL, nb10p INT DEFAULT NULL, position INT DEFAULT NULL, ffta_ranking INT DEFAULT NULL, created_at $timestampType NOT NULL, updated_at $timestampType NOT NULL, $licenseeIdNotNullFK, $eventIdNotNullFK$primaryKeyClause)");
        $this->addSql('CREATE INDEX IDX_9FA3E414734B22EE ON results (licensee_id)');
        $this->addSql('CREATE INDEX IDX_9FA3E41471F7E88B ON results (event_id)');
        $this->addSql("CREATE TABLE users (id $idType NOT NULL, email VARCHAR(180) NOT NULL, roles $jsonType NOT NULL, password VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, family_name VARCHAR(255) NOT NULL, given_name VARCHAR(255) NOT NULL, telephone VARCHAR(12) DEFAULT NULL, is_verified $booleanType NOT NULL, email_verification_token VARCHAR(255) DEFAULT NULL, email_verification_token_expires_at $timestampType DEFAULT NULL, discord_id VARCHAR(255) DEFAULT NULL, discord_access_token VARCHAR(255) DEFAULT NULL$primaryKeyClause)");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql("CREATE TABLE users_audit (id $idType NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs $jsonType DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at $timestampType NOT NULL$primaryKeyClause)");
        $this->addSql('CREATE INDEX type_5d977da36e91813e766863f2f579043a_idx ON users_audit (type)');
        $this->addSql('CREATE INDEX object_id_5d977da36e91813e766863f2f579043a_idx ON users_audit (object_id)');
        $this->addSql('CREATE INDEX discriminator_5d977da36e91813e766863f2f579043a_idx ON users_audit (discriminator)');
        $this->addSql('CREATE INDEX transaction_hash_5d977da36e91813e766863f2f579043a_idx ON users_audit (transaction_hash)');
        $this->addSql('CREATE INDEX blame_id_5d977da36e91813e766863f2f579043a_idx ON users_audit (blame_id)');
        $this->addSql('CREATE INDEX created_at_5d977da36e91813e766863f2f579043a_idx ON users_audit (created_at)');
        
        // Add foreign key constraints (database-agnostic)
        if ($isPostgreSQL) {
            $this->addSql('ALTER TABLE event_participations ADD CONSTRAINT FK_2282709B71F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
            $this->addSql('ALTER TABLE event_participations ADD CONSTRAINT FK_2282709B9D1C3019 FOREIGN KEY (participant_id) REFERENCES licensees (id)');
            $this->addSql('ALTER TABLE event_participations ADD CONSTRAINT FK_2282709B7A7B643 FOREIGN KEY (result_id) REFERENCES results (id)');
            $this->addSql('ALTER TABLE event_participations ADD CONSTRAINT FK_2282709B734B22EE FOREIGN KEY (licensee_id) REFERENCES licensees (id)');
            $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A61190A32 FOREIGN KEY (club_id) REFERENCES clubs (id)');
            $this->addSql('ALTER TABLE event_groups ADD CONSTRAINT FK_C2F44E2271F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE event_groups ADD CONSTRAINT FK_C2F44E22FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D397061190A32 FOREIGN KEY (club_id) REFERENCES clubs (id)');
            $this->addSql('ALTER TABLE group_licensees ADD CONSTRAINT FK_713C390FFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE group_licensees ADD CONSTRAINT FK_713C390F734B22EE FOREIGN KEY (licensee_id) REFERENCES licensees (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE licensees ADD CONSTRAINT FK_B18F65EEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
            $this->addSql('ALTER TABLE licenses ADD CONSTRAINT FK_7F320F3F734B22EE FOREIGN KEY (licensee_id) REFERENCES licensees (id)');
            $this->addSql('ALTER TABLE licenses ADD CONSTRAINT FK_7F320F3F61190A32 FOREIGN KEY (club_id) REFERENCES clubs (id)');
            $this->addSql('ALTER TABLE results ADD CONSTRAINT FK_9FA3E414734B22EE FOREIGN KEY (licensee_id) REFERENCES licensees (id)');
            $this->addSql('ALTER TABLE results ADD CONSTRAINT FK_9FA3E41471F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        }
        // Note: SQLite foreign key constraints are handled via PRAGMA foreign_keys=ON and inline REFERENCES in CREATE TABLE
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participations DROP CONSTRAINT FK_2282709B71F7E88B');
        $this->addSql('ALTER TABLE event_participations DROP CONSTRAINT FK_2282709B9D1C3019');
        $this->addSql('ALTER TABLE event_participations DROP CONSTRAINT FK_2282709B7A7B643');
        $this->addSql('ALTER TABLE event_participations DROP CONSTRAINT FK_2282709B734B22EE');
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_5387574A61190A32');
        $this->addSql('ALTER TABLE event_groups DROP CONSTRAINT FK_C2F44E2271F7E88B');
        $this->addSql('ALTER TABLE event_groups DROP CONSTRAINT FK_C2F44E22FE54D947');
        $this->addSql('ALTER TABLE groups DROP CONSTRAINT FK_F06D397061190A32');
        $this->addSql('ALTER TABLE group_licensees DROP CONSTRAINT FK_713C390FFE54D947');
        $this->addSql('ALTER TABLE group_licensees DROP CONSTRAINT FK_713C390F734B22EE');
        $this->addSql('ALTER TABLE licensees DROP CONSTRAINT FK_B18F65EEA76ED395');
        $this->addSql('ALTER TABLE licenses DROP CONSTRAINT FK_7F320F3F734B22EE');
        $this->addSql('ALTER TABLE licenses DROP CONSTRAINT FK_7F320F3F61190A32');
        $this->addSql('ALTER TABLE results DROP CONSTRAINT FK_9FA3E414734B22EE');
        $this->addSql('ALTER TABLE results DROP CONSTRAINT FK_9FA3E41471F7E88B');
        $this->addSql('DROP TABLE clubs');
        $this->addSql('DROP TABLE event_participations');
        $this->addSql('DROP TABLE event_participations_audit');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE event_groups');
        $this->addSql('DROP TABLE groups');
        $this->addSql('DROP TABLE group_licensees');
        $this->addSql('DROP TABLE licensees');
        $this->addSql('DROP TABLE licensees_audit');
        $this->addSql('DROP TABLE licenses');
        $this->addSql('DROP TABLE results');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_audit');
    }
}
