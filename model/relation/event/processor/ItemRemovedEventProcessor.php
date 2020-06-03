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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\relation\event\processor;

use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\taoItems\model\event\ItemRemovedEvent;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;

class ItemRemovedEventProcessor extends ConfigurableService implements EventProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function process(Event $event): void
    {
        if (!$event instanceof ItemRemovedEvent) {
            throw new InvalidEventException($event);
        }

        $id = $event->jsonSerialize()['itemUri'] ?? null;

        if (empty($id)) {
            throw new InvalidEventException($event, 'Missing itemUri');
        }

        $this->getItemRelationUpdateService()
            ->updateByTargetId((string)$id);
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }
}
