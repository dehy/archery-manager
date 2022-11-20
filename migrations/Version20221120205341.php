<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Event;
use App\Migrations\EntityMigrationInterface;
use App\Repository\EventRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221120205341 extends AbstractMigration implements EntityMigrationInterface
{
    private ?EntityManagerInterface $entityManager = null;

    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD latitude VARCHAR(10) DEFAULT NULL, ADD longitude VARCHAR(10) DEFAULT NULL, ADD all_day TINYINT(1) NOT NULL, ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function postUp(Schema $schema): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            if ('00:00' === $event->getStartsAt()->format('H:i') && '00:00' === $event->getEndsAt()->format('H:i')) {
                $event->setAllDay(true);
            }
            $event->setUpdatedAt(new \DateTimeImmutable());
        }
        $this->entityManager->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP latitude, DROP longitude, DROP all_day, DROP updated_at');
    }
}
