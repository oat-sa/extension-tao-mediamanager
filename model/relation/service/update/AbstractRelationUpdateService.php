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

namespace oat\taoMediaManager\model\relation\service\update;

use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;

abstract class AbstractRelationUpdateService extends ConfigurableService
{
    abstract protected function getRelationType(): string;

    abstract protected function createFindAllQueryForUpdate(string $sourceId): FindAllQuery;

    public function updateBySourceId(string $sourceId, array $currentMediaIds = []): void
    {
        $repository = $this->getMediaRelationRepository();

        $collection = $repository->findAll($this->createFindAllQueryForUpdate($sourceId));

        foreach ($collection->filterNewMediaIds($currentMediaIds) as $mediaId) {
            $repository->save($this->createMediaRelation($mediaId, $sourceId));
        }

        foreach ($collection->filterRemovedMediaIds($currentMediaIds) as $mediaId) {
            $repository->remove($this->createMediaRelation($mediaId, $sourceId));
        }
    }

    protected function getMediaRelationRepository(): MediaRelationRepositoryInterface
    {
        return $this->getServiceLocator()->get(MediaRelationRepositoryInterface::SERVICE_ID);
    }

    private function createMediaRelation(string $targetId, string $sourceId): MediaRelation
    {
        return (new MediaRelation($this->getRelationType(), $targetId))
            ->withSourceId($sourceId);
    }
}
