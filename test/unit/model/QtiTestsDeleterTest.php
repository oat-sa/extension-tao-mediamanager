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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\tao\model\resources\Service\ClassDeleter;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\QtiTestsDeleter;
use oat\taoMediaManager\model\TaoMediaOntology;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QtiTestsDeleterTest extends TestCase
{
    private const MEDIA_URI = 'https://media#i123';

    /** @var LoggerInterface|MockObject */
    private LoggerInterface $logger;

    /** @var Ontology|MockObject */
    private Ontology $ontology;

    /** @var core_kernel_classes_Class|MockObject */
    private core_kernel_classes_Class $mediaType;

    /** @var core_kernel_classes_Class|MockObject */
    private core_kernel_classes_Class $mediaSubclass;

    /** @var ClassDeleter|MockObject */
    private ClassDeleter $classDeleter;

    /** @var MediaService|MockObject */
    private MediaService $mediaService;

    private QtiTestsDeleter $sut;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->ontology = $this->createMock(Ontology::class);
        $this->mediaType = $this->createMock(core_kernel_classes_Class::class);
        $this->mediaSubclass = $this->createMock(core_kernel_classes_Class::class);
        $this->classDeleter = $this->createMock(ClassDeleter::class);
        $this->mediaService = $this->createMock(MediaService::class);

        $this->sut = new QtiTestsDeleter(
            $this->logger,
            $this->mediaService,
            $this->ontology,
            $this->classDeleter
        );
    }

    public function testEventWithNoReferencesTriggersNoDeletions(): void
    {
        $this->mediaService
            ->expects($this->never())
            ->method('deleteResource');

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->deleteAssetsByURIs([]);
    }

    public function testEventReferencingAnAssetWithNoSiblingsTriggersClassDeletions(): void
    {
        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn(self::MEDIA_URI);
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaSubclass,
            ]);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with(self::MEDIA_URI)
            ->willReturn($mediaResource);

        $this->mediaSubclass
            ->expects($this->once())
            ->method('countInstances')
            ->willReturn(1);
        $this->mediaSubclass
            ->expects($this->atLeastOnce())
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#subclass');

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($this->mediaSubclass);

        $this->sut->deleteAssetsByURIs([
            self::MEDIA_URI
        ]);
    }

    public function testEventReferencingAssetInTheAssetsRootSkipsClassDeletion(): void
    {
        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn(self::MEDIA_URI);
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaType,
            ]);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with(self::MEDIA_URI)
            ->willReturn($mediaResource);

        $this->mediaType
            ->expects($this->atLeastOnce())
            ->method('countInstances')
            ->willReturn(1);
        $this->mediaType
            ->expects($this->atLeastOnce())
            ->method('getUri')
            ->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->deleteAssetsByURIs([
            self::MEDIA_URI,
        ]);
    }

    public function testEventReferencingAssetWithSiblingsTriggersNoDeletions(): void
    {
        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn(self::MEDIA_URI);
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaType,
            ]);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with(self::MEDIA_URI)
            ->willReturn($mediaResource);

        $this->mediaType
            ->expects($this->atLeastOnce())
            ->method('countInstances')
            ->willReturn(2);
        $this->mediaType
            ->method('getUri')
            ->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->deleteAssetsByURIs([
            self::MEDIA_URI,
        ]);
    }
}
