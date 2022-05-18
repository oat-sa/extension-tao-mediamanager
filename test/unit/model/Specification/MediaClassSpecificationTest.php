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

namespace oat\taoMediaManager\test\unit\model\Specification;

use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Class;
use PHPUnit\Framework\TestCase;

class MediaClassSpecificationTest extends TestCase
{
    /** @var MediaClassSpecification */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new MediaClassSpecification();
    }

    /**
     * @dataProvider isSatisfiedByDataProvider
     */
    public function testIsSatisfiedBy(
        bool $expected,
        core_kernel_classes_Class $class
    ): void {
        $this->assertEquals($expected, $this->sut->isSatisfiedBy($class));
    }

    public function isSatisfiedByDataProvider(): array
    {
        return [
            'Subclass of MEDIA_ROOT' => [
                'expected' => true,
                'class' => $this->getRDFClassMock(true),
            ],
            'Not a subclass of MEDIA_ROOT' => [
                'expected' => false,
                'class' => $this->getRDFClassMock(false),
            ],
        ];
    }

    private function getRDFClassMock(bool $isSubclass): core_kernel_classes_Class
    {
        $rootClass = $this->createMock(core_kernel_classes_Class::class);
        $rootClass
            ->method('getUri')
            ->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $mock = $this->createMock(core_kernel_classes_Class::class);
        $mock
            ->method('getClass')
            ->with(TaoMediaOntology::CLASS_URI_MEDIA_ROOT)
            ->willReturn($rootClass);

        $mock
            ->expects($this->once())
            ->method('isSubclassOf')
            ->with($rootClass)
            ->willReturn($isSubclass);

        return $mock;
    }
}
