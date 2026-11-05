<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\scripts\install\RegisterMediaManagerAssetTreeBuilder;

/**
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202610061830001888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register MediaManagerAssetTreeBuilder for Resource Manager Assets browse performance';
    }

    public function up(Schema $schema): void
    {
        $this->runAction(new RegisterMediaManagerAssetTreeBuilder());
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }
}
