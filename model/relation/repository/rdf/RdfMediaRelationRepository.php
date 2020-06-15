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

use core_kernel_classes_Class as ClassResource;
use core_kernel_classes_Property;
use LogicException;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\search\base\QueryInterface;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllByTargetQuery;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;

class RdfMediaRelationRepository extends ConfigurableService implements MediaRelationRepositoryInterface
{
    use OntologyAwareTrait;

    private const ITEM_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem';
    private const MEDIA_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedMedia';

    public function findAll(FindAllQuery $findAllQuery): MediaRelationCollection
    {
        if ($findAllQuery->getClassId()) {
            return $this->findMediaWithRelations($this->getClass($findAllQuery->getClassId()));
        }

        return $this->findAllByMedia($findAllQuery->getMediaId());
    }

    private function findMediaWithRelations(ClassResource $class): MediaRelationCollection
    {
        $includedMedia = [];
        $includedMediaQueryBuilder = $this->getComplexSearchService()->query();
        $includedMediaQuery = $this->getComplexSearchService()->searchType($includedMediaQueryBuilder, $class->getUri(), true);
        $includedMediaQueryBuilder->setCriteria($includedMediaQuery);
        $includedMediaResult = $this->getComplexSearchService()->getGateway()->search($includedMediaQueryBuilder);

        foreach ($includedMediaResult as $media) {
            $includedMedia[] = $media->getUri();
        }

        $queryBuilder = $this->getComplexSearchService()->query();
        $includedMediaQuery->addCriterion(self::ITEM_RELATION_PROPERTY, SupportedOperatorHelper::IS_NOT_NULL, '');
        $queryBuilder->setCriteria($includedMediaQuery);

        $orQuery = $this->getComplexSearchService()->searchType($queryBuilder, $class->getUri(), true);
        $orQuery->addCriterion(self::MEDIA_RELATION_PROPERTY, SupportedOperatorHelper::IS_NOT_NULL, '');
        $orQuery->addCriterion(self::MEDIA_RELATION_PROPERTY, SupportedOperatorHelper::NOT_IN, $includedMedia);
        $queryBuilder->setOr($orQuery);

        $mediaResult = $this->getComplexSearchService()->getGateway()->search($queryBuilder);

        $mediaRelationCollection = new MediaRelationCollection();

        /** @var Resource $media */
        foreach ($mediaResult as $media) {
            $mediaRelationCollection->add(
                new MediaRelation(MediaRelation::MEDIA_TYPE, $media->getUri(), $media->getLabel())
            );
        }

        return $mediaRelationCollection;
    }

    public function findAllByTarget(FindAllByTargetQuery $findAllQuery): MediaRelationCollection
    {
        return $this->findAllMediaByTarget($findAllQuery->getTargetId(), $findAllQuery->getType());
    }

    public function save(MediaRelation $relation): void
    {
        $mediaResource = $this->getResource($relation->getSourceId());

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

        $this->getLogger()->info(
            sprintf(
                'Media relation saved, media "%s" is now part of %s "%s"',
                $relation->getSourceId(),
                $relation->getType(),
                $relation->getId()
            )
        );
    }

    public function remove(MediaRelation $relation): void
    {
        $mediaResource = $this->getResource($relation->getSourceId());

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

        $this->getLogger()->info(
            sprintf(
                'Media relation removed, media "%s" is not linked to %s "%s" anymore',
                $relation->getSourceId(),
                $relation->getType(),
                $relation->getId()
            )
        );
    }

    private function getPropertyByRelation(MediaRelation $mediaRelation): core_kernel_classes_Property
    {
        $uri = $mediaRelation->isMedia()
            ? self::MEDIA_RELATION_PROPERTY
            : self::ITEM_RELATION_PROPERTY;

        return $this->getProperty($uri);
    }

    private function findAllByMedia(string $mediaId): MediaRelationCollection
    {
        $mediaResource = $this->getResource($mediaId);

        $rdfMediaRelations = $mediaResource->getPropertiesValues([
            $this->getProperty(self::ITEM_RELATION_PROPERTY),
            $this->getProperty(self::MEDIA_RELATION_PROPERTY),
        ]);

        return new MediaRelationCollection(
            ... $this->mapTargetRelations(
                MediaRelation::ITEM_TYPE,
                $rdfMediaRelations[self::ITEM_RELATION_PROPERTY],
                $mediaId
            )->getIterator(),

            ... $this->mapTargetRelations(
                MediaRelation::MEDIA_TYPE,
                $rdfMediaRelations[self::MEDIA_RELATION_PROPERTY],
                $mediaId
            )->getIterator()
        );
    }

    private function findAllMediaByTarget(string $targetId, string $type): MediaRelationCollection
    {
        $search = $this->getComplexSearchService();

        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, MediaService::ROOT_CLASS_URI, true);

        $this->applyQueryTargetType($query, $targetId, $type);

        $queryBuilder->setCriteria($query);

        $result = $search->getGateway()
            ->search($queryBuilder);

        return $this->mapSourceRelations($type, (array) $result, $targetId);
    }

    private function applyQueryTargetType(QueryInterface $query, $targetId, $type)
    {
        switch ($type) {

            case MediaRelation::ITEM_TYPE:
                $query
                    ->add(self::ITEM_RELATION_PROPERTY)
                    ->equals($targetId);
                break;

            case MediaRelation::MEDIA_TYPE:
                $query
                    ->add(self::MEDIA_RELATION_PROPERTY)
                    ->equals($targetId);
                break;

            default:
                throw new LogicException('MediaRelation query type is unknown.');

        }
    }

    private function getComplexSearchService(): ComplexSearchService
    {
        return $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
    }

    /**
     * @param string $type
     * @param Resource[]
     * @param string $sourceId
     */
    private function mapTargetRelations(string $type, array $rdfMediaRelations, string $sourceId): MediaRelationCollection
    {
        $collection = new MediaRelationCollection();

        foreach ($rdfMediaRelations as $target) {
            $collection->add(
                $this->createMediaRelation($type, $target->getUri(), $sourceId, $target->getLabel())
            );
        }

        return $collection;
    }

    private function mapSourceRelations(string $type, array $rdfMediaRelations, string $targetId, string $targetLabel = ''): MediaRelationCollection
    {
        $collection = new MediaRelationCollection();

        foreach ($rdfMediaRelations as $source) {
            $collection->add(
                $this->createMediaRelation($type, $targetId, $source->subject, $targetLabel)
            );
        }

        return $collection;
    }

    private function createMediaRelation(string $type, string $targetId, string $mediaId, string $targetLabel = ''): MediaRelation
    {
        return (new MediaRelation($type, $targetId, $targetLabel))
            ->withSourceId($mediaId);
    }
}
