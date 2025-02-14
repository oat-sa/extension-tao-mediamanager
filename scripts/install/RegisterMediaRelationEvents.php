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
 * Copyright (c) 2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts\install;

use oat\generis\model\data\event\ResourceDeleted;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\resources\Event\InstanceCopiedEvent;
use oat\taoItems\model\event\ItemDuplicatedEvent;
use oat\taoMediaManager\model\relation\event\MediaRelationListener;

class RegisterMediaRelationEvents extends InstallAction
{
    public function __invoke($params): Report
    {
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            ItemDuplicatedEvent::class,
            [MediaRelationListener::class, 'whenItemIsDuplicated']
        );

        $eventManager->attach(
            InstanceCopiedEvent::class,
            [MediaRelationListener::class, 'whenInstanceCopiedEvent']
        );

        $eventManager->attach(
            ResourceDeleted::class,
            [MediaRelationListener::class, 'whenResourceIsRemoved']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        $report = new Report(Report::TYPE_SUCCESS, 'Media Relation Events Listener has been extended');
        return $report->add(Report::createInfo('Please consider running RemoveBrokenResourceRelationMap script'));
    }
}
