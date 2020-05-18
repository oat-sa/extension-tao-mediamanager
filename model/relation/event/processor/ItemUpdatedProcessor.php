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

use LogicException;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\service\ItemRelationUpdateService;

class ItemUpdatedProcessor extends ConfigurableService implements ProcessorInterface
{
    public function process(Event $event): void
    {
        $itemId = $this->getItemId($event);
        $data = $this->getData($event);

        if (array_key_exists('includedElementIds', $data)) {
            $this->getItemRelationUpdateService()
                ->updateByItem($itemId, $data['includedElementIds']);
        }
    }

    private function getItemId(Event $event): string
    {
        if (method_exists($event, 'getItemUri')) {
            return $event->getItemUri();
        }

        throw new LogicException(sprintf('Event %s does not contain method getItemUri', get_class($event)));
    }

    private function getData(Event $event): array
    {
        if (method_exists($event, 'getData')) {
            return (array)$event->getData();
        }

        throw new LogicException(sprintf('Event %s does not contain method getData', get_class($event)));
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }
}
