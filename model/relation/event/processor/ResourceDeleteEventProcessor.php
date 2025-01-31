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

namespace oat\taoMediaManager\model\relation\event\processor;

use oat\generis\model\data\event\ResourceDeleted;
use oat\oatbox\event\Event;
use oat\tao\model\TaoOntology;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;

class ResourceDeleteEventProcessor implements EventProcessorInterface
{
    private ItemRelationUpdateService $itemRelationUpdateService;

    public function __construct(ItemRelationUpdateService $itemRelationUpdateService)
    {
        $this->itemRelationUpdateService = $itemRelationUpdateService;
    }

    public function process(Event $event): void
    {
        if (!$event instanceof ResourceDeleted) {
            throw new InvalidEventException($event);
        }

        if ($event->getResourceType() === TaoOntology::CLASS_URI_ITEM) {
            $this->itemRelationUpdateService->updateByTargetId($event->getId());
        }
    }
}
