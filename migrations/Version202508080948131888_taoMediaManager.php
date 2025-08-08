<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\scripts\install\ConfigFactoryExtension;

/**
 * Re run install script
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202508080948131888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Run updated version of ConfigFactoryExtension install script';
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
