<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110185809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feature (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, prompt LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, project_id INT NOT NULL, INDEX idx_feature_deleted_at (deleted_at), INDEX idx_feature_project (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE feature_run (id INT AUTO_INCREMENT NOT NULL, user_prompt LONGTEXT NOT NULL, selected_files_json LONGTEXT DEFAULT NULL, ai_request_json LONGTEXT DEFAULT NULL, ai_response_text LONGTEXT DEFAULT NULL, patch_text LONGTEXT DEFAULT NULL, status VARCHAR(40) DEFAULT NULL, duration_ms INT DEFAULT NULL, created_at DATETIME NOT NULL, feature_id INT NOT NULL, INDEX idx_feature_run_feature (feature_id), INDEX idx_feature_run_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE feature ADD CONSTRAINT FK_1FD77566166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feature_run ADD CONSTRAINT FK_121BE17360E4B879 FOREIGN KEY (feature_id) REFERENCES feature (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feature DROP FOREIGN KEY FK_1FD77566166D1F9C');
        $this->addSql('ALTER TABLE feature_run DROP FOREIGN KEY FK_121BE17360E4B879');
        $this->addSql('DROP TABLE feature');
        $this->addSql('DROP TABLE feature_run');
    }
}
