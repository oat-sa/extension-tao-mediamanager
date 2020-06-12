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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\factory;

use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use PHPUnit\Framework\MockObject\MockObject;

class SharedStimulusResourceSpecificationTest extends TestCase
{
    /** @var SharedStimulusResourceSpecification */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontology;

    public function setUp(): void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->subject = new SharedStimulusResourceSpecification();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontology
                ]
            )
        );
    }

    public function testIsSatisfiedBy(): void
    {
        $this->assertTrue(
            $this->subject->isSatisfiedBy($this->expectsResourceWithMimeType(MediaService::SHARED_STIMULUS_MIME_TYPE))
        );
    }

    public function testIsNotSatisfiedBy(): void
    {
        $this->assertFalse(
            $this->subject->isSatisfiedBy($this->expectsResourceWithMimeType('image/jpg'))
        );
    }

    public function testIsNotSatisfiedByWhenEmptyProperty(): void
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);
        $property = $this->createMock(core_kernel_classes_Property::class);

        $this->ontology
            ->method('getProperty')
            ->willThrowException(new core_kernel_classes_EmptyProperty($resource, $property));

        $this->assertFalse($this->subject->isSatisfiedBy($resource));
    }

    private function expectsResourceWithMimeType(string $mimeType): core_kernel_classes_Resource
    {
        $this->ontology
            ->method('getProperty')
            ->willReturn($this->createMock(core_kernel_classes_Property::class));

        $resource = $this->createMock(core_kernel_classes_Resource::class);

        $resource->method('getUniquePropertyValue')
            ->willReturn(new core_kernel_classes_Literal($mimeType));

        return $resource;
    }
}
