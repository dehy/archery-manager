<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Merged migration combining:
 *   - Make target_type nullable in event_participation
 *   - Add club_equipment and equipment_loan tables
 *   - Remove DC2Type comments from columns (Doctrine 3 compatibility)
 *   - Add license_application and license_application_audit tables
 *   - Drop legacy dmishh_settings table
 *   - Add unique indexes on club(ffta_code) and club(name)
 *   - Remove pre-registration (applicant) tables
 *   - Add birthdate to user table
 *   - Add brute-force protection: user lockout fields and security_log table
 */
final class Version20251102230310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merged: nullable target_type, club equipment, license applications, remove pre-registration, user birthdate & security lockout';
    }

    public function up(Schema $schema): void
    {
        // -------------------------------------------------------------------------
        // Make target_type nullable in event_participation
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE event_participation CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') DEFAULT NULL COMMENT \'(DC2Type:TargetTypeType)\'');

        // -------------------------------------------------------------------------
        // Create club equipment tables
        // -------------------------------------------------------------------------
        $this->addSql('CREATE TABLE club_equipment (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, type ENUM(\'bow\', \'arrows\', \'quiver\', \'armguard\', \'finger_tab\', \'chest_guard\', \'other\') NOT NULL COMMENT \'(DC2Type:ClubEquipmentType)\', name VARCHAR(255) NOT NULL, serial_number VARCHAR(255) DEFAULT NULL, count INT DEFAULT NULL, bow_type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') DEFAULT NULL COMMENT \'(DC2Type:BowType)\', brand VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, limb_size INT DEFAULT NULL, limb_strength INT DEFAULT NULL, arrow_type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') DEFAULT NULL COMMENT \'(DC2Type:ArrowType)\', arrow_length INT DEFAULT NULL, arrow_spine VARCHAR(50) DEFAULT NULL, fletching_type ENUM(\'plastic\', \'spinwings\') DEFAULT NULL COMMENT \'(DC2Type:FletchingType)\', notes LONGTEXT DEFAULT NULL, is_available TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7C31756561190A32 (club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipment_loan (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, borrower_id INT NOT NULL, created_by_id INT DEFAULT NULL, start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', return_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FF57DC05517FE9FE (equipment_id), INDEX IDX_FF57DC0511CE312B (borrower_id), INDEX IDX_FF57DC05B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE club_equipment ADD CONSTRAINT FK_7C31756561190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE equipment_loan ADD CONSTRAINT FK_FF57DC05517FE9FE FOREIGN KEY (equipment_id) REFERENCES club_equipment (id)');
        $this->addSql('ALTER TABLE equipment_loan ADD CONSTRAINT FK_FF57DC0511CE312B FOREIGN KEY (borrower_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE equipment_loan ADD CONSTRAINT FK_FF57DC05B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');

        // -------------------------------------------------------------------------
        // Remove DC2Type comments (Doctrine 3 compatibility) and misc fixes
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE applicant CHANGE birthdate birthdate DATE NOT NULL, CHANGE practice_level practice_level ENUM(\'beginner\', \'intermediate\', \'advanced\') DEFAULT NULL, CHANGE registered_at registered_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE applicant_audit CHANGE diffs diffs JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE arrow CHANGE type type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') NOT NULL, CHANGE fletching fletching ENUM(\'plastic\', \'spinwings\') NOT NULL');
        $this->addSql('ALTER TABLE bow CHANGE type type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') NOT NULL');
        $this->addSql('ALTER TABLE club CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8EE3872E869DAE5 ON club (ffta_code)');
        $this->addSql('ALTER TABLE club_equipment CHANGE type type ENUM(\'bow\', \'arrows\', \'quiver\', \'armguard\', \'finger_tab\', \'chest_guard\', \'other\') NOT NULL, CHANGE bow_type bow_type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') DEFAULT NULL, CHANGE arrow_type arrow_type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') DEFAULT NULL, CHANGE fletching_type fletching_type ENUM(\'plastic\', \'spinwings\') DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE equipment_loan CHANGE start_date start_date DATETIME NOT NULL, CHANGE return_date return_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE starts_at starts_at DATETIME NOT NULL, CHANGE ends_at ends_at DATETIME NOT NULL, CHANGE contest_type contest_type ENUM(\'individual\', \'team\') DEFAULT NULL, CHANGE discipline discipline ENUM(\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\') NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE event_attachment CHANGE type type ENUM(\'mandate\', \'results\', \'misc\') NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE file_dimensions file_dimensions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation CHANGE participation_state participation_state ENUM(\'not_going\', \'interested\', \'registered\') NOT NULL, CHANGE activity activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL, CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation_audit CHANGE diffs diffs JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE license CHANGE type type ENUM(\'P\', \'J\', \'A\', \'L\', \'E\', \'S\', \'U\', \'H\', \'D\') NOT NULL, CHANGE category category ENUM(\'P\', \'J\', \'A\') DEFAULT NULL, CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') DEFAULT NULL, CHANGE activities activities LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE license_audit CHANGE diffs diffs JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE licensee CHANGE gender gender ENUM(\'M\', \'F\') NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE licensee_audit CHANGE diffs diffs JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE licensee_attachment CHANGE type type ENUM(\'profile_picture\', \'license_application\', \'medical_certificate\', \'misc\') NOT NULL, CHANGE document_date document_date DATE DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE file_dimensions file_dimensions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE practice_advice CHANGE created_at created_at DATETIME NOT NULL, CHANGE archived_at archived_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE practice_advice_audit CHANGE diffs diffs JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE practice_advice_attachment CHANGE type type ENUM(\'misc\') NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE file_dimensions file_dimensions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE result CHANGE discipline discipline ENUM(\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\') NOT NULL, CHANGE activity activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL, CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') NOT NULL, CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE gender gender ENUM(\'M\', \'F\') NOT NULL');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');

        // -------------------------------------------------------------------------
        // Create license_application tables, drop legacy dmishh_settings, add club(name) unique index
        // -------------------------------------------------------------------------
        $this->addSql('CREATE TABLE license_application (id INT AUTO_INCREMENT NOT NULL, season INT NOT NULL, status ENUM(\'pending\', \'validated\', \'waiting_list\', \'rejected\', \'cancelled\') NOT NULL, admin_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, licensee_id INT NOT NULL, club_id INT NOT NULL, processed_by_id INT DEFAULT NULL, INDEX IDX_864EC487734B22EE (licensee_id), INDEX IDX_864EC48761190A32 (club_id), INDEX IDX_864EC4872FFD4FD3 (processed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE license_application_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs JSON DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX type_5f948a911587b9c96a71f44de4f4a325_idx (type), INDEX object_id_5f948a911587b9c96a71f44de4f4a325_idx (object_id), INDEX discriminator_5f948a911587b9c96a71f44de4f4a325_idx (discriminator), INDEX transaction_hash_5f948a911587b9c96a71f44de4f4a325_idx (transaction_hash), INDEX blame_id_5f948a911587b9c96a71f44de4f4a325_idx (blame_id), INDEX created_at_5f948a911587b9c96a71f44de4f4a325_idx (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE license_application ADD CONSTRAINT FK_864EC487734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE license_application ADD CONSTRAINT FK_864EC48761190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE license_application ADD CONSTRAINT FK_864EC4872FFD4FD3 FOREIGN KEY (processed_by_id) REFERENCES `user` (id)');
        $this->addSql('DROP TABLE dmishh_settings');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8EE38725E237E06 ON club (name)');

        // -------------------------------------------------------------------------
        // Remove pre-registration (applicant) tables
        // -------------------------------------------------------------------------
        $this->addSql('DROP TABLE applicant');
        $this->addSql('DROP TABLE applicant_audit');

        // -------------------------------------------------------------------------
        // Add birthdate to user table
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE user ADD birthdate DATE DEFAULT NULL');
        $this->addSql('
            UPDATE user u
            INNER JOIN licensee l ON l.user_id = u.id
            SET u.birthdate = l.birthdate
            WHERE u.birthdate IS NULL
            AND l.birthdate IS NOT NULL
            ORDER BY l.id
        ');
        $this->addSql('UPDATE user SET birthdate = "1990-01-01" WHERE birthdate IS NULL');
        $this->addSql('ALTER TABLE user MODIFY birthdate DATE NOT NULL');

        // -------------------------------------------------------------------------
        // Add brute-force protection: user lockout fields and security_log table
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE user ADD failed_login_attempts INT DEFAULT 0 NOT NULL, ADD last_failed_login_at DATETIME DEFAULT NULL, ADD account_locked_until DATETIME DEFAULT NULL, ADD suspicious_activity_notified_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TABLE security_log (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, ip_address VARCHAR(45) NOT NULL, event_type VARCHAR(50) NOT NULL, user_agent LONGTEXT DEFAULT NULL, details LONGTEXT DEFAULT NULL, occurred_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_FE5C6A69A76ED395 (user_id), INDEX idx_occurred_at (occurred_at), INDEX idx_ip_address (ip_address), INDEX idx_event_type (event_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE security_log ADD CONSTRAINT FK_FE5C6A69A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // -------------------------------------------------------------------------
        // Undo brute-force protection
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE security_log DROP FOREIGN KEY FK_FE5C6A69A76ED395');
        $this->addSql('DROP TABLE security_log');
        $this->addSql('ALTER TABLE `user` DROP failed_login_attempts, DROP last_failed_login_at, DROP account_locked_until, DROP suspicious_activity_notified_at');

        // -------------------------------------------------------------------------
        // Undo user birthdate
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE `user` DROP birthdate');

        // -------------------------------------------------------------------------
        // Restore pre-registration (applicant) tables
        // -------------------------------------------------------------------------
        $this->addSql('CREATE TABLE applicant (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, firstname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, birthdate DATE NOT NULL, practice_level ENUM(\'beginner\', \'intermediate\', \'advanced\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, license_number VARCHAR(7) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, phone_number VARCHAR(12) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, registered_at DATETIME NOT NULL, season INT NOT NULL, renewal TINYINT NOT NULL, tournament TINYINT NOT NULL, on_waiting_list TINYINT NOT NULL, docs_retrieved TINYINT NOT NULL, paid TINYINT NOT NULL, license_created TINYINT NOT NULL, payment_observations VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE applicant_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, object_id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, discriminator VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, transaction_hash VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, diffs JSON DEFAULT NULL, blame_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, blame_user VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, blame_user_fqdn VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, blame_user_firewall VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ip VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX created_at_5019a1928c5f8c5424f0a19576c35744_idx (created_at), INDEX discriminator_5019a1928c5f8c5424f0a19576c35744_idx (discriminator), INDEX transaction_hash_5019a1928c5f8c5424f0a19576c35744_idx (transaction_hash), INDEX type_5019a1928c5f8c5424f0a19576c35744_idx (type), INDEX blame_id_5019a1928c5f8c5424f0a19576c35744_idx (blame_id), INDEX object_id_5019a1928c5f8c5424f0a19576c35744_idx (object_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        // -------------------------------------------------------------------------
        // Undo license_application tables, restore dmishh_settings, drop club(name) unique index
        // -------------------------------------------------------------------------
        $this->addSql('CREATE TABLE dmishh_settings (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, value LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE license_application DROP FOREIGN KEY FK_864EC487734B22EE');
        $this->addSql('ALTER TABLE license_application DROP FOREIGN KEY FK_864EC48761190A32');
        $this->addSql('ALTER TABLE license_application DROP FOREIGN KEY FK_864EC4872FFD4FD3');
        $this->addSql('DROP TABLE license_application');
        $this->addSql('DROP TABLE license_application_audit');
        $this->addSql('DROP INDEX UNIQ_B8EE38725E237E06 ON club');

        // -------------------------------------------------------------------------
        // Restore DC2Type comments and revert messenger_messages indexes
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE applicant CHANGE birthdate birthdate DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE practice_level practice_level ENUM(\'beginner\', \'intermediate\', \'advanced\') DEFAULT NULL COMMENT \'(DC2Type:PracticeLevelType)\', CHANGE registered_at registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE applicant_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE arrow CHANGE type type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') NOT NULL COMMENT \'(DC2Type:ArrowType)\', CHANGE fletching fletching ENUM(\'plastic\', \'spinwings\') NOT NULL COMMENT \'(DC2Type:FletchingType)\'');
        $this->addSql('ALTER TABLE bow CHANGE type type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') NOT NULL COMMENT \'(DC2Type:BowType)\'');
        $this->addSql('DROP INDEX UNIQ_B8EE3872E869DAE5 ON club');
        $this->addSql('ALTER TABLE club CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE club_equipment CHANGE type type ENUM(\'bow\', \'arrows\', \'quiver\', \'armguard\', \'finger_tab\', \'chest_guard\', \'other\') NOT NULL COMMENT \'(DC2Type:ClubEquipmentType)\', CHANGE bow_type bow_type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') DEFAULT NULL COMMENT \'(DC2Type:BowType)\', CHANGE arrow_type arrow_type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') DEFAULT NULL COMMENT \'(DC2Type:ArrowType)\', CHANGE fletching_type fletching_type ENUM(\'plastic\', \'spinwings\') DEFAULT NULL COMMENT \'(DC2Type:FletchingType)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE equipment_loan CHANGE start_date start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE return_date return_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event CHANGE discipline discipline ENUM(\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\') NOT NULL COMMENT \'(DC2Type:DisciplineType)\', CHANGE starts_at starts_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE ends_at ends_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE contest_type contest_type ENUM(\'individual\', \'team\') DEFAULT NULL COMMENT \'(DC2Type:ContestType)\'');
        $this->addSql('ALTER TABLE event_attachment CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE type type ENUM(\'mandate\', \'results\', \'misc\') NOT NULL COMMENT \'(DC2Type:EventAttachmentType)\', CHANGE file_dimensions file_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE event_participation CHANGE activity activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL COMMENT \'(DC2Type:LicenseActivityType)\', CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') NOT NULL COMMENT \'(DC2Type:TargetTypeType)\', CHANGE participation_state participation_state ENUM(\'not_going\', \'interested\', \'registered\') NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\'');
        $this->addSql('ALTER TABLE event_participation_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE license CHANGE type type ENUM(\'P\', \'J\', \'A\', \'L\', \'E\', \'S\', \'U\', \'H\', \'D\') NOT NULL COMMENT \'(DC2Type:LicenseType)\', CHANGE category category ENUM(\'P\', \'J\', \'A\') DEFAULT NULL COMMENT \'(DC2Type:LicenseCategoryType)\', CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') DEFAULT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\', CHANGE activities activities LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE licensee CHANGE gender gender ENUM(\'M\', \'F\') NOT NULL COMMENT \'(DC2Type:GenderType)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE licensee_attachment CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE type type ENUM(\'profile_picture\', \'license_application\', \'medical_certificate\', \'misc\') NOT NULL COMMENT \'(DC2Type:LicenseeAttachmentType)\', CHANGE document_date document_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE file_dimensions file_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE licensee_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE license_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE practice_advice CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE archived_at archived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE practice_advice_attachment CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE type type ENUM(\'misc\') NOT NULL COMMENT \'(DC2Type:PracticeAdviceAttachmentType)\', CHANGE file_dimensions file_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE practice_advice_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE result CHANGE discipline discipline ENUM(\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\') NOT NULL COMMENT \'(DC2Type:DisciplineType)\', CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') NOT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\', CHANGE activity activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL COMMENT \'(DC2Type:LicenseActivityType)\', CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') NOT NULL COMMENT \'(DC2Type:TargetTypeType)\'');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) NOT NULL, CHANGE gender gender ENUM(\'M\', \'F\') NOT NULL COMMENT \'(DC2Type:GenderType)\'');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // -------------------------------------------------------------------------
        // Drop club equipment tables
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE club_equipment DROP FOREIGN KEY FK_7C31756561190A32');
        $this->addSql('ALTER TABLE equipment_loan DROP FOREIGN KEY FK_FF57DC05517FE9FE');
        $this->addSql('ALTER TABLE equipment_loan DROP FOREIGN KEY FK_FF57DC0511CE312B');
        $this->addSql('ALTER TABLE equipment_loan DROP FOREIGN KEY FK_FF57DC05B03A8386');
        $this->addSql('DROP TABLE club_equipment');
        $this->addSql('DROP TABLE equipment_loan');

        // -------------------------------------------------------------------------
        // Restore target_type as NOT NULL in event_participation
        // -------------------------------------------------------------------------
        $this->addSql('ALTER TABLE event_participation CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') NOT NULL COMMENT \'(DC2Type:TargetTypeType)\'');
    }
}
