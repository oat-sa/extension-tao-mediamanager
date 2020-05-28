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

use core_kernel_classes_Property;
use LogicException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllByTargetQuery;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\query\FindAllByMediaQuery;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMapInterface;

class RdfMediaRelationRepository extends ConfigurableService implements MediaRelationRepositoryInterface
{
    use OntologyAwareTrait;

    /** @var string */
    public const MAP_OPTION = 'map';

    public function findAll(FindAllQuery $findAllQuery): MediaRelationCollection
    {
        if ($findAllQuery instanceof FindAllByMediaQuery) {
            return $this->findAllByMedia($findAllQuery->getMediaId());
        }

        if ($findAllQuery instanceof FindAllByTargetQuery) {
            return $this->getRelationMap($findAllQuery->getType())
                ->findAllByTarget($findAllQuery->getTargetId());
        }

        throw new LogicException('Invalid query filter');
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
                $relation->getId(),
                $relation->getType(),
                $relation->getSourceId()
            )
        );
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

            $rdfMaps[$map->getTargetType()] = $this->propagate($map);
        }

        return $rdfMaps;
    }

    private function getPropertyByRelation(MediaRelation $mediaRelation): core_kernel_classes_Property
    {
        return $this->getProperty(
            $this->getRelationMap($mediaRelation->getType())->getMediaRelationPropertyUri()
        );
    }

    private function getRelationMap(string $type): RdfMediaRelationMapInterface
    {
        $map = $this->getRdfRelationMediaMaps();

        if (!isset($map[$type])) {
            throw new LogicException(
                sprintf('Cannot find media relation for unknown type "%"', $type)
            );
        }

        return $map[$type];
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
}
