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
use oat\tao\model\media\TaoMediaException;
use oat\tao\model\media\TaoMediaResolver;
use oat\tao\model\resources\Exception\ClassDeletionException;
use oat\tao\model\resources\Exception\PartialClassDeletionException;
use oat\tao\model\resources\Service\ClassDeleter;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoQtiTest\models\event\QtiTestsDeletedEvent;
use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use tao_helpers_Uri;
use Psr\Log\LoggerInterface;
use Exception;
use Throwable;

class QtiTestsDeletedListener
{
    private LoggerInterface $logger;
    private Ontology $ontology;
    private ClassDeleter $classDeleter;
    private TaoMediaResolver $taoMediaResolver;
    private MediaClassSpecification $mediaClassSpecification;
    private MediaService $mediaService;

    public function __construct(
        LoggerInterface $logger,
        MediaService $mediaService,
        MediaClassSpecification $mediaClassSpecification,
        Ontology $ontology,
        ClassDeleter $classDeleter,
        TaoMediaResolver $taoMediaResolver
    ) {
        $this->logger = $logger;
        $this->mediaService = $mediaService;
        $this->mediaClassSpecification = $mediaClassSpecification;
        $this->ontology = $ontology;
        $this->classDeleter = $classDeleter;
        $this->taoMediaResolver = $taoMediaResolver;
    }

    /**
     * @throws PartialClassDeletionException
     * @throws ClassDeletionException
     * @throws Exception
     */
    public function handle(QtiTestsDeletedEvent $event): void
    {
        $assetIds = [];

        foreach (array_unique($event->getReferencedResources()) as $ref) {
            try {
                $id = $this->taoMediaResolver->resolve($ref)->getMediaIdentifier();

                if (!isset($assetIds[$id])) {
                    $assetIds[$id] = $id;

                    $this->deleteAsset($id);
                }
            } catch (TaoMediaException $e) {
                // This is expected: Some media may not come from MediaManager
                $this->logger->info(
                    sprintf(
                        'Media "%s" is not handled by MediaManager: %s',
                        $ref,
                        $e->getMessage()
                    )
                );
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        '%s exception deleting "%s": %s',
                        get_class($e),
                        $ref,
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

        $this->logger->debug(sprintf('Remove asset: %s', $uri));
        $resource = $this->ontology->getResource($uri);

        if ($this->isMediaResource($resource)) {
            $this->logger->debug(
                sprintf('isMedia=true, deleting %s', $resource->getUri())
            );

            $type = current($resource->getTypes());

            if ($this->resourceHasNoSiblings($resource)) {
                $this->logger->debug(
                    sprintf(
                        'Class %s for media %s only contains the resource being' .
                        'deleted, deferring deletion for the class as well',
                        $type->getUri(),
                        $resource->getUri()
                    )
                );

                $this->logger->debug(
                    sprintf('Deleting class %s [%s]', $type->getLabel(), $type->getUri())
                );

                $this->classDeleter->delete($type);
            }

            $this->mediaService->deleteResource($resource);
        }
    }

    private function isMediaResource(core_kernel_classes_Resource $resource): bool
    {
        foreach ($resource->getTypes() as $type) {
            if ($this->mediaClassSpecification->isSatisfiedBy($type)) {
                return true;
            }
        }

        return false;
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
