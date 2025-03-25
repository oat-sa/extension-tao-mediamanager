<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\scripts\install\ConfigFactoryExtension;
use oat\taoQtiItem\model\service\CreatorConfigFactory;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202502241322561888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register FE MediaManager details';
    }

    public function up(Schema $schema): void
    {
        $this->runAction(new ConfigFactoryExtension());
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }
}
