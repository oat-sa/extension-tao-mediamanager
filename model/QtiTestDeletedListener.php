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
use oat\taoQtiTest\models\event\QtiTestDeletedEvent;
use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use tao_helpers_Uri;
use Psr\Log\LoggerInterface;
use Exception;

class QtiTestDeletedListener
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
    public function handleQtiTestDeletedEvent(QtiTestDeletedEvent $event): void
    {
        $assetIds = [];

        foreach (array_unique($event->getReferencedResources()) as $ref) {
            try {
                $asset = $this->taoMediaResolver->resolve($ref);
                $assetIds[] = $asset->getMediaIdentifier();
            } catch (TaoMediaException $e) {
                $this->logger->debug(
                    sprintf('Unable to resolve "%s": %s', $ref, $e->getMessage())
                );
            }
        }

        $this->deleteAssets($assetIds);
    }

    /**
     * @throws PartialClassDeletionException
     * @throws ClassDeletionException
     */
    private function deleteAssets(array $assetIds): void
    {
        $this->logger->debug(
            sprintf('Removing referenced assets: %s', implode(', ',  $assetIds))
        );

        $classesToDelete = [];

        foreach ($assetIds as $assetId) {
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
                            'Class %s for media %s only contains the resource being'.
                            'deleted, deferring deletion for the class as well',
                            $type->getUri(),
                            $resource->getUri()
                        )
                    );

                    $classesToDelete[] = $type;
                }

                $this->mediaService->deleteResource($resource);
            }
        }

        $this->deleteClasses($classesToDelete);
    }

    /**
     * @param core_kernel_classes_Class[] $classes
     *
     * @throws ClassDeletionException
     * @throws PartialClassDeletionException
     */
    private function deleteClasses(array $classes): void
    {
        if (empty($classes)) {
            $this->logger->debug('No deferred class deletions to be performed');
            return;
        }

        $this->logger->debug(
            sprintf(
                'Performing deferred deletions for %s empty classes',
                count($classes)
            )
        );

        foreach ($classes as $class) {
            $this->logger->debug(
                sprintf('Deleting class %s [%s]', $class->getLabel(), $class->getUri())
            );

            $this->classDeleter->delete($class);
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
