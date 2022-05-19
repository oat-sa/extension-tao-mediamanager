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

    /** @var ClassCopierInterface */
    private $subClassCopier;

    public function __construct(
        LoggerInterface $logger,
        RootClassesListServiceInterface $rootClassesListService,
        ClassSpecificationInterface $mediaClassSpecification,
        ClassPropertyCopierInterface $classPropertyCopier,
        InstanceCopierInterface $instanceCopier
    ) {
        $this->subClassCopier = $this;

        $this->logger = $logger;
        $this->rootClassesListService = $rootClassesListService;
        $this->mediaClassSpecification = $mediaClassSpecification;
        $this->classPropertyCopier = $classPropertyCopier;
        $this->instanceCopier = $instanceCopier;
    }

    /**
     * Used from tests
     */
    public function withSubClassCopier(AssetClassCopier $copier): void
    {
        $this->subClassCopier = $copier;
    }

    public function copy(
        core_kernel_classes_Class $class,
        core_kernel_classes_Class $destinationClass
    ): core_kernel_classes_Class {
        $this->assertInAssetsRootClass($class);
        $this->assertInAssetsRootClass($destinationClass);
        $this->assertInSameRootClass($class, $destinationClass);

        $newClass = $destinationClass->createSubClass($class->getLabel());

        foreach ($class->getProperties(false) as $property) {
            $this->classPropertyCopier->copy($property, $newClass);
        }

        foreach ($class->getInstances() as $instance) {
            $this->instanceCopier->copy($instance, $newClass);
        }

        foreach ($class->getSubClasses() as $subClass) {
            $this->subClassCopier->copy($subClass, $newClass);
        }

        return $newClass;
    }

    private function assertInAssetsRootClass(core_kernel_classes_Class $class): void
    {
        if (!$this->mediaClassSpecification->isSatisfiedBy($class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Selected class (%s) is not supported because it is not part of the media assets root class (%s).',
                    $class->getUri(),
                    TaoMediaOntology::CLASS_URI_MEDIA_ROOT
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
