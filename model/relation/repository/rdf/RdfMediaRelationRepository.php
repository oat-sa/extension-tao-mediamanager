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

namespace oat\taoMediaManager\model\relation\repository\rdf;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfItemRelationMap;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMap;

class RdfMediaRelationRepository extends ConfigurableService implements MediaRelationRepositoryInterface
{
    use OntologyAwareTrait;

    /**
     * Find all mediaRelation based on FindAllQuery.
     *
     * Find and aggregate item mediaRelations and media mediaRelations
     *
     * @param FindAllQuery $findAllQuery
     * @return MediaRelationCollection
     */
    public function findAll(FindAllQuery $findAllQuery): MediaRelationCollection
    {
        $mediaResource = $this->getResource($findAllQuery->getMediaId());
        $mediaRelationCollections = new MediaRelationCollection();

        foreach ($this->getRdfRelationMediaMaps() as $relationMediaMap) {
            $relationMediaMap->getMediaRelations($mediaResource, $mediaRelationCollections);
        }

        return $mediaRelationCollections;
    }

    public function save(MediaRelation $relation): void
    {
        // TODO: Implement save() method.
    }

    public function remove(MediaRelation $relation): void
    {
        // TODO: Implement remove() method.
    }

    private function getRdfRelationMediaMaps(): array
    {
        return [
            new RdfItemRelationMap(),
            new RdfMediaRelationMap(),
        ];
    }
}
