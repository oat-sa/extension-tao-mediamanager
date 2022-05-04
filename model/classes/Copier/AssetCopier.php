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


namespace oat\taoMediaManager\model\classes\Copier;

use core_kernel_classes_Class;
use InvalidArgumentException;
use oat\tao\model\resources\Contract\ClassCopierInterface;
use oat\tao\model\resources\Contract\RootClassesListServiceInterface;
use oat\taoMediaManager\model\TaoMediaOntology;

class AssetCopier implements ClassCopierInterface
{
    private const ROOT_CLASS_URI = TaoMediaOntology::CLASS_MEDIA_ROOT_URI;

    /** @var RootClassesListServiceInterface */
    private $rootClassesListService;

    public function __construct(
        RootClassesListServiceInterface $rootClassesListService
    ) {
        $this->rootClassesListService = $rootClassesListService;
    }

    /**
     * ACs:
     *
     * - The "copy process" should be processed in the queue in the background.
     *      -Handled by tao_actions_RdfController::copyClasS() @ tao-core
     *
     *
     * - Assets related to items will keep the reference to the original assets
     *   (no asset duplication required).
     *      - This seems correct, since it seems asset files themselves are kept
     *        (only RDF data in the DB is removed)
     *
     * - We must keep class hierarchy while copying.
     *      - i.e. Recursive copying
     *
     * @param core_kernel_classes_Class $class
     * @param core_kernel_classes_Class $destinationClass
     * @return core_kernel_classes_Class
     */

    public function copy(
        core_kernel_classes_Class $class,
        core_kernel_classes_Class $destinationClass
    ): core_kernel_classes_Class {
        $this->assertInAssetsRootClass($class);
        $this->assertInAssetsRootClass($destinationClass);

        // Needed, otherwise it could be properties defined in the source class
        // schema that doesn't exist in the destination
        $this->assertInSameRootClass($class, $destinationClass);

        // TODO: Implement copy() method.
    }

    private function assertInAssetsRootClass(core_kernel_classes_Class $class): void
    {
        if (!$class->isSubClassOf($class->getClass(self::ROOT_CLASS_URI))) {
            throw new InvalidArgumentException(
                sprintf(
                    'Selected class (%s) is not supported because it is not part of the media assets root class (%s).',
                    $class->getUri(),
                    self::ROOT_CLASS_URI
                )
            );
        }
    }

    // @todo May be moved into a specification (to be reused by tao-core's ClassCopier)
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
