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
use oat\taoItems\model\event\ItemUpdatedEvent;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;

class ItemUpdatedEventProcessor extends ConfigurableService implements EventProcessorInterface
{
    private const INCLUDE_ELEMENT_IDS_KEY = 'includeElementIds';
    private const OBJECT_ELEMENT_IDS_KEY = 'objectElementIds';
    private const IMG_ELEMENT_IDS_KEY = 'imgElementIds';

    /**
     * @inheritDoc
     */
    public function process(Event $event): void
    {
        if (!$event instanceof ItemUpdatedEvent) {
            throw new InvalidEventException($event);
        }

        $data = $event->getData();

        if ($this->mustUpdateItemRelation($data)) {
            $this->getItemRelationUpdateService()
                ->updateByTargetId($event->getItemUri(), $this->getAggregatedMediaIds($data));
        }
    }

    private function mustUpdateItemRelation(array $data): bool
    {
        return array_key_exists(self::INCLUDE_ELEMENT_IDS_KEY, $data)
            || array_key_exists(self::OBJECT_ELEMENT_IDS_KEY, $data)
            || array_key_exists(self::IMG_ELEMENT_IDS_KEY, $data);
    }

    private function getAggregatedMediaIds(array $data): array
    {
        return array_merge(
            $data[self::INCLUDE_ELEMENT_IDS_KEY] ?? [],
            $data[self::OBJECT_ELEMENT_IDS_KEY] ?? [],
            $data[self::IMG_ELEMENT_IDS_KEY] ?? []
        );
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }
}
