<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\ContestEvent;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221115093357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD slug VARCHAR(255) NOT NULL');

        $slugify = new Slugify();
        $events = $this->connection->fetchAllAssociative('SELECT * FROM event');
        foreach ($events as $eventAssoc) {
            $event = new ContestEvent();
            $event->setKind($eventAssoc['type'])
                ->setContestDiscipline($eventAssoc['discipline'])
                ->setName($eventAssoc['name'])
                ->setStartsAt(new \DateTimeImmutable($eventAssoc['starts_at']))
            ;
            $slug = $slugify->slugify(
                sprintf('%s-%s', $event->getStartTime()->format('d-m-Y'), $event->getName())
            );
            $this->addSql('UPDATE event SET slug = :slug WHERE id = :id', ['slug' => $slug, 'id' => $eventAssoc['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP slug');
    }
}
