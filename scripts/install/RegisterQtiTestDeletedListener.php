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

namespace oat\taoMediaManager\scripts\install;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\reporting\Report;
use oat\taoMediaManager\model\QtiTestDeletedListener;
use oat\taoQtiTest\models\event\QtiTestDeletedEvent;

class RegisterQtiTestDeletedListener extends InstallAction
{
    public function __invoke($params = []): Report
    {
        $eventManager = $this->getEventManager();
        $eventManager->attach(
            QtiTestDeletedEvent::class,
            [QtiTestDeletedListener::class, 'handleQtiTestDeletedEvent']
        );

        $this->persistEventManagerConfig($eventManager);

        return Report::createSuccess(
            sprintf(
                'Successfully registered listener %s for event %s',
                QtiTestDeletedListener::class . '::handleQtiTestDeletedEvent',
                QtiTestDeletedEvent::class
            )
        );
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceManager()->get(EventManager::SERVICE_ID);
    }

    private function persistEventManagerConfig(EventManager &$eventManager): void
    {
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
