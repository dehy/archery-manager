<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121195221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE clubs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE events_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE licensees_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE licenses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE clubs (id INT NOT NULL, sport VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, logo_name VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, email VARCHAR(255) NOT NULL, ffta_code VARCHAR(7) NOT NULL, ffta_username VARCHAR(255) DEFAULT NULL, ffta_password VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, address_country VARCHAR(255) DEFAULT NULL, address_locality VARCHAR(255) DEFAULT NULL, address_postal_code VARCHAR(255) DEFAULT NULL, address_address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5BD31235E237E06 ON clubs (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5BD3123E869DAE5 ON clubs (ffta_code)');
        $this->addSql('COMMENT ON COLUMN clubs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN clubs.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE events (id INT NOT NULL, sport VARCHAR(255) NOT NULL, organizer VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, address_country VARCHAR(255) DEFAULT NULL, address_locality VARCHAR(255) DEFAULT NULL, address_postal_code VARCHAR(255) DEFAULT NULL, address_address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN events.end_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN events.start_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE licensees (id INT NOT NULL, user_id INT DEFAULT NULL, gender VARCHAR(255) NOT NULL, family_name VARCHAR(255) NOT NULL, given_name VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, ffta_member_code VARCHAR(8) DEFAULT NULL, ffta_id INT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B18F65EE68FC69F7 ON licensees (ffta_member_code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B18F65EEBD6F80AD ON licensees (ffta_id)');
        $this->addSql('CREATE INDEX IDX_B18F65EEA76ED395 ON licensees (user_id)');
        $this->addSql('COMMENT ON COLUMN licensees.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE licensees_audit (id SERIAL NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs JSON DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX type_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (type)');
        $this->addSql('CREATE INDEX object_id_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (object_id)');
        $this->addSql('CREATE INDEX discriminator_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (discriminator)');
        $this->addSql('CREATE INDEX transaction_hash_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (transaction_hash)');
        $this->addSql('CREATE INDEX blame_id_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (blame_id)');
        $this->addSql('CREATE INDEX created_at_5322d3ef7e5221d11c9970d6afd74559_idx ON licensees_audit (created_at)');
        $this->addSql('COMMENT ON COLUMN licensees_audit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE licenses (id INT NOT NULL, licensee_id INT NOT NULL, club_id INT NOT NULL, season INT NOT NULL, type VARCHAR(255) NOT NULL, category VARCHAR(255) DEFAULT NULL, age_category VARCHAR(255) DEFAULT NULL, activities TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7F320F3F734B22EE ON licenses (licensee_id)');
        $this->addSql('CREATE INDEX IDX_7F320F3F61190A32 ON licenses (club_id)');
        $this->addSql('COMMENT ON COLUMN licenses.activities IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, family_name VARCHAR(255) NOT NULL, given_name VARCHAR(255) NOT NULL, telephone VARCHAR(12) DEFAULT NULL, is_verified BOOLEAN NOT NULL, discord_id VARCHAR(255) DEFAULT NULL, discord_access_token VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TABLE users_audit (id SERIAL NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs JSON DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX type_5d977da36e91813e766863f2f579043a_idx ON users_audit (type)');
        $this->addSql('CREATE INDEX object_id_5d977da36e91813e766863f2f579043a_idx ON users_audit (object_id)');
        $this->addSql('CREATE INDEX discriminator_5d977da36e91813e766863f2f579043a_idx ON users_audit (discriminator)');
        $this->addSql('CREATE INDEX transaction_hash_5d977da36e91813e766863f2f579043a_idx ON users_audit (transaction_hash)');
        $this->addSql('CREATE INDEX blame_id_5d977da36e91813e766863f2f579043a_idx ON users_audit (blame_id)');
        $this->addSql('CREATE INDEX created_at_5d977da36e91813e766863f2f579043a_idx ON users_audit (created_at)');
        $this->addSql('COMMENT ON COLUMN users_audit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE licensees ADD CONSTRAINT FK_B18F65EEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE licenses ADD CONSTRAINT FK_7F320F3F734B22EE FOREIGN KEY (licensee_id) REFERENCES licensees (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE licenses ADD CONSTRAINT FK_7F320F3F61190A32 FOREIGN KEY (club_id) REFERENCES clubs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE clubs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE events_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE licensees_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE licenses_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE licensees DROP CONSTRAINT FK_B18F65EEA76ED395');
        $this->addSql('ALTER TABLE licenses DROP CONSTRAINT FK_7F320F3F734B22EE');
        $this->addSql('ALTER TABLE licenses DROP CONSTRAINT FK_7F320F3F61190A32');
        $this->addSql('DROP TABLE clubs');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE licensees');
        $this->addSql('DROP TABLE licensees_audit');
        $this->addSql('DROP TABLE licenses');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_audit');
    }
}
