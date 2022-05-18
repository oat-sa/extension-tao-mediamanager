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

namespace oat\taoMediaManager\test\unit\model;

use oat\tao\model\resources\Contract\ClassPropertyCopierInterface;
use oat\tao\model\resources\Contract\InstanceCopierInterface;
use oat\tao\model\resources\Contract\RootClassesListServiceInterface;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\classes\Copier\AssetInstanceContentCopier;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoItems\model\Copier\ItemClassCopier;
use oat\tao\model\resources\Contract\ClassCopierInterface;
use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use oat\tao\model\TaoOntology;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

class AssetClassCopierTest extends TestCase
{
    private const ASSET_ROOT_CLASS_URI = TaoMediaOntology::CLASS_URI_MEDIA_ROOT;

    private const ERRMSG_NOT_IN_ASSETS_ROOT =
        'Selected class (%s) is not supported because it is not part of the media assets root class (%s).';

    private const ERRMSG_NOT_IN_SAME_ROOT =
        'Selected class (%s) and destination class (%s) must be in the same root class (%s).';

    /** @var ItemClassCopier */
    private $sut;

    /** @var ClassCopierInterface|MockObject */
    private $classCopier;

    /** @var MediaClassSpecification|MockObject */
    private $mediaClassSpecification;

    /** @var RootClassesListServiceInterface|MockObject */
    private $rootClassesListService;

    /** @var ClassPropertyCopierInterface|MockObject */
    private $classPropertyCopier;

    /** @var InstanceCopierInterface|MockObject */
    private $instanceCopier;

    /** @var core_kernel_classes_Class|MockObject
     * @todo Needed?
     */
    private $classMedia;

    protected function setUp(): void
    {
        $this->classMedia = $this->createMock(core_kernel_classes_Class::class);

        $this->classCopier = $this->createMock(ClassCopierInterface::class);
        $this->mediaClassSpecification = $this->createMock(
            MediaClassSpecification::class
        );
        $this->rootClassesListService = $this->createMock(
            RootClassesListServiceInterface::class
        );
        $this->classPropertyCopier = $this->createMock(
            ClassPropertyCopierInterface::class
        );
        $this->instanceCopier = $this->createMock(
            InstanceCopierInterface::class
        );

        $this->sut = new AssetClassCopier(
            $this->createMock(LoggerInterface::class),
            $this->rootClassesListService,
            $this->mediaClassSpecification,
            $this->classPropertyCopier,
            $this->instanceCopier
        );
    }

    /*public function testCopyInvalidSourceClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            sprintf(
                self::ERRMSG_NOT_IN_ASSETS_ROOT,
                self::ASSET_ROOT_CLASS_URI
            )
        );
    }

    public function testCopyInvalidDestinationClass(): void
    {

    }

    public function testClassMismatch(): void
    {

    }*/

    /*public function testCopy(): void
    {
        // @todo
        /*$rootClass = $this->createMock(core_kernel_classes_Class::class);

        $class = $this->createMock(core_kernel_classes_Class::class);
        $class
            ->expects($this->once())
            ->method('getClass')
            ->with(TaoOntology::CLASS_URI_ITEM)
            ->willReturn($rootClass);
        $class
            ->expects($this->once())
            ->method('equals')
            ->with($rootClass)
            ->willReturn(true);
        $class
            ->expects($this->never())
            ->method('isSubClassOf');
        $class
            ->expects($this->never())
            ->method('getUri');

        $destinationClass = $this->createMock(core_kernel_classes_Class::class);
        $newClass = $this->createMock(core_kernel_classes_Class::class);

        $this->classCopier
            ->expects($this->once())
            ->method('copy')
            ->with($class, $destinationClass)
            ->willReturn($newClass);

        $this->assertEquals($newClass, $this->sut->copy($class, $destinationClass));
    }*/

    /*public function testCopyInvalidClass(): void
    {
        $rootClass = $this->createMock(core_kernel_classes_Class::class);

        $classUri = 'classUri';

        $class = $this->createMock(core_kernel_classes_Class::class);
        $class
            ->expects($this->once())
            ->method('getClass')
            ->with(TaoOntology::CLASS_URI_ITEM)
            ->willReturn($rootClass);
        $class
            ->expects($this->once())
            ->method('equals')
            ->with($rootClass)
            ->willReturn(false);
        $class
            ->expects($this->once())
            ->method('isSubClassOf')
            ->with($rootClass)
            ->willReturn(false);
        $class
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($classUri);

        $this->classCopier
            ->expects($this->never())
            ->method('copy');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Selected class (%s) is not supported because it is not part of the items root class (%s).',
                $classUri,
                TaoOntology::CLASS_URI_ITEM
            )
        );

        $this->sut->copy($class, $this->createMock(core_kernel_classes_Class::class));
    }*/
}
