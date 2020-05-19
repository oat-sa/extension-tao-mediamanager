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

use common_exception_Error;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use LogicException;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\search\base\exception\SearchGateWayExeption;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfItemRelationMap;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMap;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMapInterface;

class RdfMediaRelationRepository extends ConfigurableService implements MediaRelationRepositoryInterface
{
    use OntologyAwareTrait;

    /** @var string */
    public const MAP_OPTION = 'map';

    public function findAll(FindAllQuery $findAllQuery): MediaRelationCollection
    {
        if ($findAllQuery->getMediaId()) {
            return $this->findAllByMedia($findAllQuery->getMediaId());
        }

        if ($findAllQuery->getItemId()) {
            return $this->findAllByItem($findAllQuery->getItemId());
        }

        throw new LogicException('Invalid query filter');
    }

    public function save(MediaRelation $relation): void
    {
        $mediaResource = $this->getModel()->getResource($relation->getSourceId());

        if (!$mediaResource->setPropertyValue($this->getPropertyByRelation($relation), $relation->getId())) {
            throw new LogicException(
                sprintf(
                    'Error saving media relation %s [%s:%s]',
                    $relation->getType(),
                    $relation->getSourceId(),
                    $relation->getId()
                )
            );
        }
    }

    public function remove(MediaRelation $relation): void
    {
        $mediaResource = $this->getModel()->getResource($relation->getSourceId());

        if (!$mediaResource->removePropertyValue($this->getPropertyByRelation($relation), $relation->getId())) {
            throw new LogicException(
                sprintf(
                    'Error removing media relation %s [%s:%s]',
                    $relation->getType(),
                    $relation->getSourceId(),
                    $relation->getId()
                )
            );
        }
    }

    /**
     * @return RdfMediaRelationMapInterface[]
     */
    private function getRdfRelationMediaMaps(): array
    {
        $rdfMaps = [];
        $maps = $this->getOption(self::MAP_OPTION);

        if (!is_array($maps)) {
            throw new LogicException('Rdf map for media relation has to be array');
        }

        foreach ($maps as $map) {
            if (!is_a($map, RdfMediaRelationMapInterface::class)) {
                throw new LogicException(
                    sprintf('Rdf map for media relation required to implement "%s"', RdfMediaRelationMapInterface::class)
                );
            }

            $rdfMaps[] = $map;
        }

        return $rdfMaps;
    }

    private function getPropertyByRelation(MediaRelation $mediaRelation): core_kernel_classes_Property
    {
        return $this->getProperty(
            $mediaRelation->isMedia()
                ? RdfMediaRelationMap::MEDIA_RELATION_PROPERTY
                : RdfItemRelationMap::ITEM_RELATION_PROPERTY
        );
    }

    /**
     * @throws common_exception_Error
     * @throws SearchGateWayExeption
     */
    private function findAllByItem(string $itemId): MediaRelationCollection
    {
        $search = $this->getComplexSearchService();

        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, MediaService::ROOT_CLASS_URI, true)
            ->add(RdfItemRelationMap::ITEM_RELATION_PROPERTY)
            ->equals($itemId);

        $queryBuilder->setCriteria($query);

        $result = $search->getGateway()
            ->search($queryBuilder);

        $mapper = $this->getRdfMediaRelationMap();

        $mediaRelationCollections = new MediaRelationCollection();

        /** @var core_kernel_classes_Resource $resource */
        foreach ($result as $resource) {
            $mediaRelationCollections->add($mapper->createMediaRelation($resource, $itemId));
        }

        return $mediaRelationCollections;
    }

    private function findAllByMedia(string $mediaId): MediaRelationCollection
    {
        $mediaRelationCollections = new MediaRelationCollection();
        $mediaResource = $this->getResource($mediaId);

        foreach ($this->getRdfRelationMediaMaps() as $relationMediaMap) {
            $relationMediaMap->mapMediaRelations($mediaResource, $mediaRelationCollections);
        }

        return $mediaRelationCollections;
    }

    private function getComplexSearchService(): ComplexSearchService
    {
        return $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
    }

    private function getRdfMediaRelationMap(): RdfMediaRelationMap
    {
        return $this->getServiceLocator()->get(RdfMediaRelationMap::class);
    }
}
