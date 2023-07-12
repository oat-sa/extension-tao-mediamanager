<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\oatbox\reporting\Report;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\taoMediaManager\model\QtiTestsDeletedListener;
use oat\taoQtiTest\models\event\QtiTestsDeletedEvent;

final class Version202307101523571228_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registers QtiTestDeletedListener for deletion events in order to update media relations';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->attach(
            QtiTestsDeletedEvent::class,
            [QtiTestsDeletedListener::class, 'handle']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Successfully registered listener %s for event %s',
                    QtiTestsDeletedListener::class . '::handle',
                    QtiTestsDeletedEvent::class
                )
            )
        );
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'Reverting this migration requires a code change'
        );
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceManager()->get(EventManager::SERVICE_ID);
    }
}
