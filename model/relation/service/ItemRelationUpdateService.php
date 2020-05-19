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

namespace oat\taoMediaManager\model\relation\service;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;

class ItemRelationUpdateService extends ConfigurableService
{
    use OntologyAwareTrait;

    public function updateByItem(string $itemId, array $currentMediaIds = []): void
    {
        $repository = $this->getMediaRelationRepository();

        $collection = $repository->findAll(new FindAllQuery(null, $itemId));

        foreach ($collection->filterNewMediaIds($currentMediaIds) as $mediaId) {
            $repository->save($this->createMediaRelation(MediaRelation::ITEM_TYPE, $itemId, $mediaId));
        }

        foreach ($collection->filterRemovedMediaIds($currentMediaIds) as $mediaId) {
            $repository->remove($this->createMediaRelation(MediaRelation::ITEM_TYPE, $itemId, $mediaId));
        }
    }

    public function removeMedia(string $mediaId): void
    {
        $repository = $this->getMediaRelationRepository();
        $medias = $repository->findAll(new FindAllQuery($mediaId))->getIterator();

        /** @var MediaRelation $media */
        foreach ($medias as $media) {
            $repository->remove($media->withSourceId($mediaId));
        }
    }

    private function createMediaRelation(string $type, string $targetId, string $sourceId): MediaRelation
    {
        return (new MediaRelation($type, $targetId))->withSourceId($sourceId);
    }

    private function getMediaRelationRepository(): MediaRelationRepositoryInterface
    {
        return $this->getServiceLocator()->get(MediaRelationRepositoryInterface::SERVICE_ID);
    }
}
