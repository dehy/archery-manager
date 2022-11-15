<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Event;
use App\Migrations\EntityMigrationInterface;
use App\Repository\EventRepository;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221115093357 extends AbstractMigration implements EntityMigrationInterface
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
        $this->addSql('ALTER TABLE event ADD slug VARCHAR(255) NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $slugify = new Slugify();

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $event->setSlug(
                $slugify->slugify(
                    sprintf('%s-%s', $event->getStartsAt()->format('d-m-Y'), $event->getTitle())
                )
            );
        }
        $this->entityManager->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP slug');
    }
}
