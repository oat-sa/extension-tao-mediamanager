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
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoItems\model\Copier\ItemClassCopier;
use oat\tao\model\resources\Contract\ClassCopierInterface;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
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

    /** @var core_kernel_classes_Class|MockObject */
    private $source;

    /** @var core_kernel_classes_Class|MockObject */
    private $target;

    /** @var ItemClassCopier */
    private $sut;

    protected function setUp(): void
    {
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

        $this->mediaClassSpecification
            ->method('isSatisfiedBy')
            ->willReturnCallback(function (core_kernel_classes_Class $class) {
                return in_array(
                    $class->getUri(),
                    [
                        'http://asset.root/1',
                        'http://asset.root/1/1',
                        'http://asset.root/2',
                        'http://asset.root/2/1',
                        'http://asset.root/1/c1',
                        'http://asset.root/1/c2',
                    ]
                );
            });

        $classRoot1 = $this->createMock(core_kernel_classes_Class::class);
        $classRoot1->method('getUri')->willReturn('http://asset.root/1');

        $classRoot2 = $this->createMock(core_kernel_classes_Class::class);
        $classRoot2->method('getUri')->willReturn('http://asset.root/2');

        $this->rootClassesListService
            ->method('list')
            ->willReturn([$classRoot1, $classRoot2]);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $this->source = $this->createMock(core_kernel_classes_Class::class);
        $this->target = $this->createMock(core_kernel_classes_Class::class);

        $this->sut = new AssetClassCopier(
            $loggerMock,
            $this->rootClassesListService,
            $this->mediaClassSpecification,
            $this->classPropertyCopier,
            $this->instanceCopier
        );
    }

    /**
     * @dataProvider copyInvalidClassTypesDataProvider
     */
    public function testCopyInvalidClassType(
        string $sourceUri,
        string $targetUri,
        string $unsupportedClass
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                self::ERRMSG_NOT_IN_ASSETS_ROOT,
                $unsupportedClass,
                self::ASSET_ROOT_CLASS_URI
            )
        );

        $this->source->method('getUri')->willReturn($sourceUri);
        $this->target->method('getUri')->willReturn($targetUri);

        $this->sut->copy($this->source, $this->target);
    }

    public function copyInvalidClassTypesDataProvider(): array
    {
        return [
            'Copy from a non-assets class type' => [
                'sourceUri' => 'http://test.root/1',
                'targetUri' => 'http://asset.root/2',
                'unsupportedClass' => 'http://test.root/1',
            ],
            'Copy to a non-assets class type' => [
                'sourceUri' => 'http://asset.root/1',
                'targetUri' => 'http://item.root/2',
                'unsupportedClass' => 'http://item.root/2',
            ],
        ];
    }

    public function testClassRootMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                self::ERRMSG_NOT_IN_SAME_ROOT,
                'http://asset.root/1/1',
                'http://asset.root/2/1',
                'http://asset.root/1'
            )
        );

        $this->source->method('getUri')->willReturn('http://asset.root/1/1');
        $this->source
            ->method('isSubclassOf')
            ->willReturnCallback(function (core_kernel_classes_Class $class) {
                return $class->getUri() === 'http://asset.root/1';
            });

        $this->target->method('getUri')->willReturn('http://asset.root/2/1');
        $this->target
            ->method('isSubclassOf')
            ->willReturnCallback(function (core_kernel_classes_Class $class) {
                return $class->getUri() === 'http://asset.root/2';
            });

        $this->sut->copy($this->source, $this->target);
    }

    public function testCopy(): void
    {
        $this->source->method('getLabel')->willReturn('Source Class');
        $this->source->method('getUri')->willReturn('http://asset.root/1/c1');
        $this->target->method('getUri')->willReturn('http://asset.root/1/c2');

        $newClass = $this->createMock(core_kernel_classes_Class::class);

        $propertyMocks = [
            $this->createMock(core_kernel_classes_Property::class),
            $this->createMock(core_kernel_classes_Property::class),
        ];

        $this->target
            ->expects($this->once())
            ->method('createSubClass')
            ->with('Source Class')
            ->willReturn($newClass);

        $this->source
            ->method('getProperties')
            ->with(false)
            ->willReturn($propertyMocks);

        $this->classCopier
            ->method('copy')
            ->withConsecutive([
                [$propertyMocks[0], $newClass],
                [$propertyMocks[1], $newClass],
            ]);

        $instances = [
            $this->createMock(core_kernel_classes_Resource::class),
            $this->createMock(core_kernel_classes_Resource::class),
            $this->createMock(core_kernel_classes_Resource::class),
        ];

        $this->instanceCopier
            ->expects($this->exactly(3))
            ->method('copy')
            ->withConsecutive(
                [$instances[0], $newClass],
                [$instances[1], $newClass],
                [$instances[2], $newClass]
            );

        $this->source->method('getInstances')->willReturn($instances);
        $this->source->method('getSubclasses')->willReturn([]);

        $this->assertEquals(
            $newClass,
            $this->sut->copy($this->source, $this->target)
        );
    }

    public function testCopyRecursive(): void
    {
        $this->source->method('getLabel')->willReturn('Source Class');
        $this->source->method('getUri')->willReturn('http://asset.root/1/c1');
        $this->target->method('getUri')->willReturn('http://asset.root/1/c2');

        $newClass = $this->createMock(core_kernel_classes_Class::class);

        $this->target
            ->expects($this->once())
            ->method('createSubClass')
            ->with('Source Class')
            ->willReturn($newClass);

        $this->source
            ->method('getProperties')
            ->with(false)
            ->willReturn([]);

        $this->classCopier
            ->expects($this->never())
            ->method('copy');

        $this->instanceCopier
            ->expects($this->never())
            ->method('copy');

        $subClass = $this->createMock(core_kernel_classes_Class::class);

        $this->source->method('getInstances')->willReturn([]);
        $this->source->method('getSubclasses')->willReturn([$subClass]);

        $subClassCopier = $this->createMock(AssetClassCopier::class);
        $subClassCopier
            ->expects($this->once())
            ->method('copy')
            ->with($subClass, $newClass)
            ->willReturn($newClass);

        $this->sut->withSubClassCopier($subClassCopier);

        $this->assertEquals(
            $newClass,
            $this->sut->copy($this->source, $this->target)
        );
    }
}
