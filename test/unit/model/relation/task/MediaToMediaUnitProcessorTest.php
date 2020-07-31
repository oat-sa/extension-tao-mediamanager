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

namespace oat\taoMediaManager\test\unit\model\relation\task;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\tao\model\task\migration\ResourceResultUnit;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\relation\service\update\MediaRelationUpdateService;
use oat\taoMediaManager\model\relation\task\MediaToMediaUnitProcessor;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use PHPUnit\Framework\MockObject\MockObject;

class MediaToMediaUnitProcessorTest extends TestCase
{
    /** @var MediaToMediaUnitProcessor */
    private $subject;

    /** @var MediaRelationUpdateService|MockObject */
    private $mediaRelationUpdateService;

    /** @var SharedStimulusMediaExtractor|MockObject */
    private $sharedStimulusMediaExtractor;

    /** @var FileManagement|MockObject */
    private $fileManagement;

    /** @var SharedStimulusResourceSpecification|MockObject */
    private $sharedStimulusResourceSpecification;

    /** @var Ontology|MockObject */
    private $ontology;

    public function setUp(): void
    {
        $this->mediaRelationUpdateService = $this->createMock(MediaRelationUpdateService::class);
        $this->sharedStimulusMediaExtractor = $this->createMock(SharedStimulusMediaExtractor::class);
        $this->fileManagement = $this->createMock(FileManagement::class);
        $this->sharedStimulusResourceSpecification = $this->createMock(SharedStimulusResourceSpecification::class);
        $this->ontology = $this->createMock(Ontology::class);
        $this->subject = new MediaToMediaUnitProcessor();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    MediaRelationUpdateService::class => $this->mediaRelationUpdateService,
                    SharedStimulusMediaExtractor::class => $this->sharedStimulusMediaExtractor,
                    FileManagement::SERVICE_ID => $this->fileManagement,
                    SharedStimulusResourceSpecification::class => $this->sharedStimulusResourceSpecification,
                    Ontology::SERVICE_ID => $this->ontology,
                ]
            )
        );
    }

    public function testProcess(): void
    {
        $file = $this->createMock(File::class);
        $resource = $this->createMock(core_kernel_classes_Resource::class);
        $property = $this->createMock(core_kernel_classes_Property::class);

        $uri = 'abc123';
        $fileUri = ['ref'];
        $mediaIds = ['id'];
        $fileContent = 'abc123';

        $resource->method('getUniquePropertyValue')
            ->willReturn($property);

        $property->method('getUri')
            ->willReturn($fileUri);

        $file->method('read')
            ->willReturn($fileContent);

        $this->ontology
            ->method('getResource')
            ->with($uri)
            ->willReturn($resource);

        $this->ontology
            ->method('getProperty')
            ->willReturn($property);

        $this->fileManagement
            ->method('getFileStream')
            ->with($fileUri)
            ->willReturn($file);

        $this->sharedStimulusMediaExtractor
            ->method('extractMediaIdentifiers')
            ->with($fileContent)
            ->willReturn($mediaIds);

        $this->mediaRelationUpdateService
            ->method('updateByTargetId')
            ->with($uri, $mediaIds);

        $this->sharedStimulusResourceSpecification
            ->method('isSatisfiedBy')
            ->with($resource)
            ->willReturn(true);

        $this->assertNull($this->subject->process(new ResourceResultUnit($uri)));
    }
}
