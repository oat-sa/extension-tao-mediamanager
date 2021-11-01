<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\scripts\install\RegisterXinludeHandler;
use oat\tao\model\ClientLibConfigRegistry;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202107260926321888_taoMediaManager extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register Rich passage handler to xincludeRenderer. It will add passage custom styles to head';
    }

    public function up(Schema $schema): void
    {
        $this->propagate(new RegisterXinludeHandler())([]);
    }

    public function down(Schema $schema): void
    {
        ClientLibConfigRegistry::getRegistry()->remove('taoQtiItem/qtiCreator/helper/xincludeRenderer');
    }
}
