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
use oat\tao\model\resources\Service\ClassDeleter;
use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use tao_helpers_Uri;
use Psr\Log\LoggerInterface;
use Throwable;

class AssetDeleter
{
    private LoggerInterface $logger;
    private Ontology $ontology;
    private ClassDeleter $classDeleter;
    private MediaService $mediaService;
    private RdfMediaRelationRepository $mediaRelationRepository;

    public function __construct(
        LoggerInterface $logger,
        MediaService $mediaService,
        Ontology $ontology,
        ClassDeleter $classDeleter,
        RdfMediaRelationRepository $mediaRelationRepository
    ) {
        $this->logger = $logger;
        $this->mediaService = $mediaService;
        $this->ontology = $ontology;
        $this->classDeleter = $classDeleter;
        $this->mediaRelationRepository = $mediaRelationRepository;
    }

    public function deleteByItemUri(string $itemUri): void
    {
        foreach ($this->getAssetsToDeleteByItemUri($itemUri) as $assetUri) {
            try {
                $this->deleteAsset($assetUri);

                $this->logger->info(
                    sprintf(
                        'Assets "%s" removed after Item "%s" using them was removed',
                        $assetUri,
                        $itemUri
                    )
                );
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'ItemRemovedEventProcessor: CAUGHT %s exception removing assets: %s - trace: %s',
                        get_class($e),
                        $e->getMessage(),
                        $e->getTraceAsString()
                    )
                );
            }
        }
    }

    private function getAssetsToDeleteByItemUri(string $itemUri): array
    {
        $assetsToDelete = [];

        foreach ($this->mediaRelationRepository->getItemAssetUris($itemUri) as $assetUri) {
            $relatedItemUris = $this->mediaRelationRepository->getRelatedItemUrisByAssetUri($assetUri);

            if (count($relatedItemUris) == 1) {
                $assetsToDelete[] = $assetUri;
            }
        }

        return $assetsToDelete;
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
        $this->mediaService->deleteResource($resource);

        if ($hasNoSiblings) {
            $this->classDeleter->delete($type);
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
