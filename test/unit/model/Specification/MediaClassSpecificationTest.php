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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MediaClassSpecificationTest extends TestCase
{
    /** @var core_kernel_classes_Class|MockObject */
    private $rootClass;

    /** @var core_kernel_classes_Class|MockObject */
    private $testedClass;

    /** @var MediaClassSpecification */
    private $sut;

    public function setUp(): void
    {
        $this->rootClass = $this->createMock(core_kernel_classes_Class::class);
        $this->rootClass
            ->method('getUri')
            ->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $this->testedClass = $this->createMock(core_kernel_classes_Class::class);
        $this->testedClass
            ->method('getClass')
            ->with(TaoMediaOntology::CLASS_URI_MEDIA_ROOT)
            ->willReturn($this->rootClass);

        $this->sut = new MediaClassSpecification();
    }

    /**
     * @dataProvider isSatisfiedByDataProvider
     */
    public function testIsSatisfiedBy(
        bool $expected,
        bool $isSubclass
    ): void {
        $this->testedClass
            ->expects($this->once())
            ->method('isSubclassOf')
            ->with($this->rootClass)
            ->willReturn($isSubclass);

        $this->assertEquals(
            $expected,
            $this->sut->isSatisfiedBy($this->testedClass)
        );
    }

    public function isSatisfiedByDataProvider(): array
    {
        return [
            'Subclass of MEDIA_ROOT' => [
                'expected' => true,
                'isSubclass' => true,
            ],
            'Not a subclass of MEDIA_ROOT' => [
                'expected' => false,
                'isSubclass' => false,
            ],
        ];
    }
}
