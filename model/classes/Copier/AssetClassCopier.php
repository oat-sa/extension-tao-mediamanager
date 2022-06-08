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

use oat\tao\model\resources\Contract\ClassCopierInterface;
use oat\tao\model\resources\Contract\ClassPropertyCopierInterface;
use oat\tao\model\resources\Contract\RootClassesListServiceInterface;
use oat\tao\model\resources\Service\ClassPropertyCopier;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Class;
use InvalidArgumentException;

class AssetClassCopier implements ClassCopierInterface
{
    /** @var RootClassesListServiceInterface */
    private $rootClassesListService;

    /** @var ClassSpecificationInterface */
    private $mediaClassSpecification;

    /** @var ClassCopierInterface */
    private $taoClassCopier;

    public function __construct(
        RootClassesListServiceInterface $rootClassesListService,
        ClassSpecificationInterface $mediaClassSpecification,
        ClassCopierInterface $taoClassCopier
    ) {
        $this->rootClassesListService = $rootClassesListService;
        $this->mediaClassSpecification = $mediaClassSpecification;
        $this->taoClassCopier = $taoClassCopier;
    }

    public function copy(
        core_kernel_classes_Class $class,
        core_kernel_classes_Class $destinationClass
    ): core_kernel_classes_Class {
        $this->assertInAssetsRootClass($class);
        $this->assertInAssetsRootClass($destinationClass);

        return $this->taoClassCopier->copy($class, $destinationClass);
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
}
