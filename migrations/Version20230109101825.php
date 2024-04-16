<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\Licensee;
use App\Helper\LicenseHelper;
use App\Repository\LicenseeRepository;
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
        $this->addSql('ALTER TABLE event DROP `kind`');
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
        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $entityManager->getRepository(Licensee::class);

        $connection = $entityManager->getConnection();
        $participations = $connection
            ->executeQuery('SELECT * FROM event_participation')
            ->fetchAllAssociative();

        $updateQuery = 'UPDATE event_participation SET activity = :activity, target_type = :target_type';
        $updateParams = [];
        foreach ($participations as $participation) {
            $event = $connection->executeQuery('SELECT * FROM event WHERE id = :id', ['id' => $participation['event_id']]);
            $season = LicenseHelper::getSeasonForDate(new \DateTime($event['starts_at']));
            $participant = $licenseeRepository->find($participation['participant_id']);
            $license = $participant->getLicenseForSeason($season);
            $activity = $license->getActivities()[0];
            $targetType = LicenseActivityType::CO === $activity ? TargetTypeType::TRISPOT : TargetTypeType::MONOSPOT;

            $updateParams[] = ['activity' => $activity, 'target_type' => $targetType];
        }

        $connection->beginTransaction();
        foreach ($updateParams as $updateParam) {
            $connection->executeQuery($updateQuery, $updateParams);
        }
        $connection->commit();
    }
}
