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

namespace oat\taoMediaManager\model\relation\repository\rdf\map;

use common_exception_Error;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\search\base\exception\SearchGateWayExeption;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use core_kernel_classes_Resource as RdfResource;

abstract class AbstractRdfMediaRelationMap extends ConfigurableService implements RdfMediaRelationMapInterface
{
    use OntologyAwareTrait;

    public function mapMediaRelations(
        RdfResource $source,
        MediaRelationCollection $mediaRelationCollection
    ): void
    {
        $mediaRelations = $source->getPropertyValues($this->getProperty($this->getMediaRelationPropertyUri()));

        foreach ($mediaRelations as $mediaRelation) {
            $mediaRelationResource = $this->getResource($mediaRelation);
            $mediaRelationCollection->add(
                $this->createMediaRelation($mediaRelationResource, $source->getUri())
            );
        }
    }

    /**
     * @throws common_exception_Error
     * @throws SearchGateWayExeption
     */
    public function findAllByTarget(string $targetId): MediaRelationCollection
    {
        $search = $this->getComplexSearchService();

        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, MediaService::ROOT_CLASS_URI, true)
            ->add($this->getMediaRelationPropertyUri())
            ->equals($targetId);

        $queryBuilder->setCriteria($query);

        $result = $search->getGateway()
            ->search($queryBuilder);

        $mediaRelationCollections = new MediaRelationCollection();

        /** @var RdfResource $resource */
        foreach ($result as $resource) {

            $mediaRelationCollections->add(
                $this->createMediaRelation($this->getResource($targetId), $resource->getUri())
            );
        }

        return $mediaRelationCollections;
    }

    private function createMediaRelation(RdfResource $mediaResource, string $sourceId): MediaRelation
    {
        return (new MediaRelation($this->getTargetType(), $mediaResource->getUri(), $mediaResource->getLabel()))
            ->withSourceId($sourceId);
    }

    private function getComplexSearchService(): ComplexSearchService
    {
        return $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
    }
}
