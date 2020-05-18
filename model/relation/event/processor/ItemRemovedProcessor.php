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

use JsonSerializable;
use LogicException;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\service\ItemRelationUpdateService;

class ItemRemovedProcessor extends ConfigurableService implements ProcessorInterface
{
    public function process(Event $event): void
    {
        $this->getItemRelationUpdateService()
            ->updateByItem($this->getItemId($event));
    }

    private function getItemId(Event $event): string
    {
        if (!$event instanceof JsonSerializable) {
            throw new LogicException(sprintf('Event must %s implement JsonSerializable', get_class($event)));
        }

        $id = $event->jsonSerialize()['itemUri'] ?? null;

        if (empty($id)) {
            throw new LogicException(sprintf('Event %s does not contain itemUri', get_class($event)));
        }

        return (string)$id;
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }
}
