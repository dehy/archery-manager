<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201105434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
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
        $this->addSql('ALTER TABLE event_participation CHANGE activity activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL COMMENT \'(DC2Type:LicenseActivityType)\', CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') DEFAULT NULL COMMENT \'(DC2Type:TargetTypeType)\', CHANGE participation_state participation_state ENUM(\'not_going\', \'interested\', \'registered\') NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\'');
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
    }
}
