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

use oat\oatbox\event\Event;
use oat\tao\model\resources\Event\InstanceCopiedEvent;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\service\ItemMediaCollector;

class EventInstanceCopiedProcessor implements EventProcessorInterface
{
    private MediaRelationRepositoryInterface $mediaRelationRepository;
    private ItemMediaCollector $itemMediaCollector;

    public function __construct(MediaRelationRepositoryInterface $mediaRelationRepository, ItemMediaCollector $itemMediaCollector)
    {
        $this->mediaRelationRepository = $mediaRelationRepository;
        $this->itemMediaCollector = $itemMediaCollector;
    }

    /**
     * @param InstanceCopiedEvent $event
     */
    public function process(Event $event): void
    {
        if (!$event instanceof InstanceCopiedEvent) {
            throw new InvalidEventException($event);
        }

        foreach ($this->itemMediaCollector->getItemMediaResources($event->getOriginInstanceUri()) as $mediaUri) {
            $mediaRelation = new MediaRelation(MediaRelation::ITEM_TYPE, $event->getInstanceUri());
            $mediaRelation->withSourceId($mediaUri);
            $this->mediaRelationRepository->save($mediaRelation);
        }
    }
}
