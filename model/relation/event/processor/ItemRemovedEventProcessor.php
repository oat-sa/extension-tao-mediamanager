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
 * Copyright (c) 2020-2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\relation\event\processor;

use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\taoItems\model\event\ItemRemovedEvent;
use oat\taoMediaManager\model\AssetDeleter;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
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

        $data = $event->jsonSerialize();
        $id = $data[ItemRemovedEvent::PAYLOAD_KEY_ITEM_URI] ?? null;
        $deleteRelatedAssets = $data[ItemRemovedEvent::PAYLOAD_KEY_DELETE_RELATED_ASSETS] ?? false;

        if (empty($id)) {
            throw new InvalidEventException($event, 'Missing itemUri');
        }

        if ($deleteRelatedAssets) {
            $collection = $this->getMediaRelationRepository()->getItemAssetUris($id);

            $this->getQtiTestsDeleter()->deleteAssetsByURIs($collection);

            $this->getLogger()->info(
                sprintf(
                    'Assets "%s" removed after Item "%s" using them was removed ',
                    json_encode($collection),
                    $id
                )
            );
        }

        $this->getItemRelationUpdateService()
            ->updateByTargetId((string)$id);
    }

    private function getQtiTestsDeleter(): AssetDeleter
    {
        return $this->getServiceLocator()->getContainer()->get(AssetDeleter::class);
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }

    private function getMediaRelationRepository(): MediaRelationRepositoryInterface
    {
        return $this->getServiceLocator()->get(RdfMediaRelationRepository::class);
    }
}
