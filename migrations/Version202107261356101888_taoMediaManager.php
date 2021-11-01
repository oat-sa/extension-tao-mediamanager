<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\scripts\install\RegisterItemDataHandler;
use oat\tao\model\ClientLibConfigRegistry;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202107261356101888_taoMediaManager extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register Rich passage handler to previewer/proxy. It will add passage custom styles to itemData';
    }

    public function up(Schema $schema): void
    {
        $this->propagate(new RegisterItemDataHandler())([]);
    }

    public function down(Schema $schema): void
    {
        ClientLibConfigRegistry::getRegistry()->remove('taoQtiTestPreviewer/previewer/proxy/itemDataHandlers');
    }
}
