<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\model\export\service\MediaResourcePreparer;
use oat\taoMediaManager\scripts\install\RegisterMediaResourcePreparer;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202111151250521888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return sprintf('Perform \'%s\' ', RegisterMediaResourcePreparer::class);
    }

    public function up(Schema $schema): void
    {
        $this->addReport(
            $this->propagate(
                new RegisterMediaResourcePreparer()
            )(
                ['service' => MediaResourcePreparer::class]
            )
        );
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'Reversing this migration requires a code change'
        );
    }
}
