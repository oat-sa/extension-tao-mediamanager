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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA.
 */

namespace oat\taoMediaManager\model;

use oat\generis\model\data\Ontology;
use oat\tao\model\resources\Exception\ClassDeletionException;
use oat\tao\model\resources\Exception\PartialClassDeletionException;
use oat\tao\model\resources\relation\ResourceRelation;
use oat\tao\model\resources\Service\ClassDeleter;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllByTargetQuery;
use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use tao_helpers_Uri;
use Psr\Log\LoggerInterface;
use Throwable;

class AssetDeleter
{
    private LoggerInterface $logger;
    private Ontology $ontology;
    private ClassDeleter $classDeleter;
    private MediaService $mediaService;
    private MediaRelationRepositoryInterface $mediaRelationRepository;

    public function __construct(
        LoggerInterface $logger,
        MediaService $mediaService,
        Ontology $ontology,
        ClassDeleter $classDeleter,
        MediaRelationRepositoryInterface $mediaRelationRepository
    ) {
        $this->logger = $logger;
        $this->mediaService = $mediaService;
        $this->ontology = $ontology;
        $this->classDeleter = $classDeleter;
        $this->mediaRelationRepository = $mediaRelationRepository;
    }

    public function deleteAssetsByURIs(array $ids): void
    {
        foreach ($ids as $id) {
            try {
                $this->deleteAsset($id);
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        '%s exception deleting "%s": %s',
                        get_class($e),
                        $id,
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * @throws PartialClassDeletionException
     * @throws ClassDeletionException
     */
    private function deleteAsset(string $assetId): void
    {
        $uri = tao_helpers_Uri::decode($assetId);

        $resource = $this->ontology->getResource($uri);
        $type = current($resource->getTypes());

        $hasNoSiblings = $this->resourceHasNoSiblings($resource);

        $this->deleteAssetRelations($uri);
        $this->mediaService->deleteResource($resource);

        if ($hasNoSiblings) {
            $this->classDeleter->delete($type);
        }
    }

    private function deleteAssetRelations(string $assetUri): void
    {
        $relations = $this->mediaRelationRepository->findAllByTarget(
            new FindAllByTargetQuery($assetUri, MediaRelation::MEDIA_TYPE)
        );

        $logger = \common_Logger::singleton()->getLogger();

        foreach ($relations as $relation) {
            /** @var ResourceRelation $relation */
            $logger->info("sourceId: {$relation->getSourceId()}");

            // @todo Remove the relation
        }
    }

    private function resourceHasNoSiblings(core_kernel_classes_Resource $resource): bool
    {
        $type = current($resource->getTypes());

        return count($resource->getTypes()) == 1
            && $type instanceof core_kernel_classes_Class
            && $type->countInstances() == 1
            && $type->getUri() !== TaoMediaOntology::CLASS_URI_MEDIA_ROOT;
    }
}
