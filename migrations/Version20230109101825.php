<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\EventParticipation;
use App\Helper\LicenseHelper;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230109101825 extends AbstractMigration implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation ADD activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL COMMENT \'(DC2Type:LicenseActivityType)\', ADD target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') NOT NULL COMMENT \'(DC2Type:TargetTypeType)\'');
        $this->addSql('ALTER TABLE event ADD `discr` VARCHAR(255) NOT NULL, CHANGE `type` `kind` ENUM(\'training\', \'contest_official\', \'contest_hobby\', \'other\') NOT NULL COMMENT \'(DC2Type:EventKindType)\', CHANGE `contest_type` `contest_type` VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE event SET discr = kind');
        $this->addSql('UPDATE event SET contest_type = \'individual\' WHERE contest_type IS NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE `contest_type` `contest_type` ENUM(\'individual\', \'team\') DEFAULT NULL COMMENT \'(DC2Type:ContestType)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation DROP activity, DROP target_type');
    }

    public function setContainer(?ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postUp(Schema $schema): void
    {
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $eventParticipationRepository = $entityManager->getRepository(EventParticipation::class);

        /** @var EventParticipation[] $participations */
        $participations = $eventParticipationRepository->findAll();
        foreach ($participations as $participation) {
            $season = LicenseHelper::getSeasonForDate($participation->getEvent()->getStartsAt());
            $license = $participation->getParticipant()->getLicenseForSeason($season);
            $activity = $license->getActivities()[0];
            $targetType = LicenseActivityType::CO === $activity ? TargetTypeType::TRISPOT : TargetTypeType::MONOSPOT;

            $participation->setActivity($activity);
            $participation->setTargetType($targetType);
        }

        $entityManager->flush();
    }
}
