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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\classes\Copier;

use core_kernel_classes_Class;
use InvalidArgumentException;
use oat\tao\model\resources\Contract\ClassCopierInterface;
use oat\tao\model\resources\Contract\ClassPropertyCopierInterface;
use oat\tao\model\resources\Contract\InstanceCopierInterface;
use oat\tao\model\resources\Contract\RootClassesListServiceInterface;
use oat\tao\model\resources\Service\ClassPropertyCopier;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoMediaManager\model\TaoMediaOntology;
use Psr\Log\LoggerInterface;

class AssetClassCopier implements ClassCopierInterface
{
    private const ROOT_CLASS_URI = TaoMediaOntology::CLASS_URI_MEDIA_ROOT;

    /** @var LoggerInterface */
    private $logger;

    /** @var RootClassesListServiceInterface */
    private $rootClassesListService;

    /** @var ClassPropertyCopier */
    private $classPropertyCopier;

    /** @var ClassSpecificationInterface */
    private $mediaClassSpecification;

    /** @var InstanceCopierInterface */
    private $instanceCopier;

    public function __construct(
        LoggerInterface $logger,
        RootClassesListServiceInterface $rootClassesListService,
        ClassSpecificationInterface $mediaClassSpecification,
        ClassPropertyCopierInterface $classPropertyCopier,
        InstanceCopierInterface $instanceCopier
    ) {
        $this->logger = $logger;
        $this->rootClassesListService = $rootClassesListService;
        $this->mediaClassSpecification = $mediaClassSpecification;
        $this->classPropertyCopier = $classPropertyCopier;
        $this->instanceCopier = $instanceCopier;
    }

    /**
     * ACs:
     *
     * - The "copy process" should be processed in the queue in the background.
     *   -> Handled by tao_actions_RdfController::copyClass() @ tao-core
     *
     * - Assets related to items will keep the reference to the original assets
     *   (no asset duplication required).
     *   -> Consistent with asset removal, since it seems in that case asset
     *      files themselves are kept: only RDF data in the DB is removed.
     *
     * - We must keep class hierarchy while copying.
     *   -> i.e. Recursive copying
     *
     * @param core_kernel_classes_Class $class
     * @param core_kernel_classes_Class $destinationClass
     *
     * @return core_kernel_classes_Class
     */
    public function copy(
        core_kernel_classes_Class $class,
        core_kernel_classes_Class $destinationClass
    ): core_kernel_classes_Class {
        $this->debug(__FUNCTION__);

        $this->assertInAssetsRootClass($class);
        $this->assertInAssetsRootClass($destinationClass);
        $this->assertInSameRootClass($class, $destinationClass);

        $newClass = $destinationClass->createSubClass($class->getLabel());
        $this->debug(
            'Created new subclass %s under %s',
            $newClass->getUri(),
            $class->getUri()
        );

        $this->debug('Iterating properties');
        foreach ($class->getProperties(false) as $property) {
            $this->debug(
                'Copying property %s to %s',
                $property->getUri(),
                $newClass->getUri()
            );

            $this->classPropertyCopier->copy($property, $newClass);
        }

        $this->debug('Iterating instances');
        foreach ($class->getInstances() as $instance) {
            $this->debug(
                '%s copying instance %s into %s',
                get_class($this->instanceCopier),
                $instance->getUri(),
                $newClass->getUri()
            );

            $this->instanceCopier->copy($instance, $newClass);
        }

        $this->debug('Iterating subclasses');
        foreach ($class->getSubClasses() as $subClass) {
            $this->debug(
                'Copying subclass %s (%s) to %s (%s)',
                $subClass->getUri(),
                $subClass->getLabel(),
                $newClass->getUri(),
                $newClass->getLabel()
            );

            $this->copy($subClass, $newClass);
        }

        $this->debug('Returning class %s', $newClass->getLabel());
        return $newClass;
    }

    // @todo To be deleted before merge
    private function debug(string $format, string ...$va_args): void
    {
        $this->logger->info(__CLASS__ . ' MM ' . vsprintf($format, $va_args));
    }

    private function assertInAssetsRootClass(core_kernel_classes_Class $class): void
    {
        if (!$this->mediaClassSpecification->isSatisfiedBy($class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Selected class (%s) is not supported because it is not part of the media assets root class (%s).',
                    $class->getUri(),
                    self::ROOT_CLASS_URI
                )
            );
        }
    }

    private function assertInSameRootClass(
        core_kernel_classes_Class $class,
        core_kernel_classes_Class $destinationClass
    ): void {
        foreach ($this->rootClassesListService->list() as $rootClass) {
            if ($class->isSubClassOf($rootClass) && !$destinationClass->isSubClassOf($rootClass)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Selected class (%s) and destination class (%s) must be in the same root class (%s).',
                        $class->getUri(),
                        $destinationClass->getUri(),
                        $rootClass->getUri()
                    )
                );
            }
        }
    }
}
