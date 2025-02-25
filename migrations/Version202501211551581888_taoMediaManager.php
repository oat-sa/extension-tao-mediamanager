<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoItems\model\event\ItemDuplicatedEvent;
use oat\taoMediaManager\model\relation\event\MediaRelationListener;
use oat\taoMediaManager\scripts\install\RegisterMediaRelationEvents;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202501211551581888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This will register the event listener for item duplication';
    }

    public function up(Schema $schema): void
    {
        $this->runAction(new RegisterMediaRelationEvents(), []);
    }

    public function down(Schema $schema): void
    {
        $this->getServiceManager()->get(EventManager::SERVICE_ID)
            ->detach(ItemDuplicatedEvent::class, [MediaRelationListener::class, 'whenItemIsDuplicated']);
    }
}
