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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaResolver;
use oat\tao\model\resources\Service\ClassDeleter;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\QtiTestDeletedListener;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoQtiTest\models\event\QtiTestDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QtiTestDeletedListenerTest extends TestCase
{
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

    /** @var TaoMediaResolver|MockObject */
    private TaoMediaResolver $taoMediaResolver;

    /** @var MediaClassSpecification|MockObject */
    private MediaClassSpecification $mediaClassSpecification;

    /** @var MediaService|MockObject */
    private MediaService $mediaService;

    /** @var QtiTestDeletedEvent|MockObject */
    private QtiTestDeletedEvent $event;

    private QtiTestDeletedListener $sut;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->ontology = $this->createMock(Ontology::class);
        $this->mediaType = $this->createMock(core_kernel_classes_Class::class);
        $this->mediaSubclass = $this->createMock(core_kernel_classes_Class::class);
        $this->classDeleter = $this->createMock(ClassDeleter::class);
        $this->taoMediaResolver = $this->createMock(TaoMediaResolver::class);
        $this->mediaClassSpecification = $this->createMock(MediaClassSpecification::class);
        $this->mediaService = $this->createMock(MediaService::class);
        $this->event = $this->createMock(QtiTestDeletedEvent::class);

        $this->sut = new QtiTestDeletedListener(
            $this->logger,
            $this->mediaService,
            $this->mediaClassSpecification,
            $this->ontology,
            $this->classDeleter,
            $this->taoMediaResolver
        );
    }

    public function testEventWithNoReferencesTriggersNoDeletions(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getReferencedResources')
            ->willReturn([]);

        $this->mediaService
            ->expects($this->never())
            ->method('deleteResource');

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->handleQtiTestDeletedEvent($this->event);
    }

    public function testEventReferencingAnAssetWithNoSiblingsTriggersClassDeletions(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getReferencedResources')
            ->willReturn([
                'taomedia://asset/1',
            ]);

        $asset = $this->createMock(MediaAsset::class);
        $asset
            ->expects($this->once())
            ->method('getMediaIdentifier')
            ->willReturn('https_2_host_1_ontologies_1_tao_0_rdf_3_i123456789abcdef0123456789abcdef01');

        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01');
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaSubclass,
            ]);

        $this->taoMediaResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('taomedia://asset/1')
            ->willReturn($asset);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01')
            ->willReturn($mediaResource);

        $this->mediaSubclass
            ->expects($this->once())
            ->method('countInstances')
            ->willReturn(1);
        $this->mediaSubclass
            ->expects($this->atLeastOnce())
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#subclass');

        $this->mediaClassSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->mediaSubclass)
            ->willReturn(true);

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($this->mediaSubclass);

        $this->sut->handleQtiTestDeletedEvent($this->event);
    }

    public function testEventWithDuplicatedReferencesDoesNotDeleteAssetsTwice(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getReferencedResources')
            ->willReturn([
                'taomedia://asset/1',
                'taomedia://asset/1',
                'taomedia://asset/1',
            ]);

        $asset = $this->createMock(MediaAsset::class);
        $asset
            ->expects($this->once())
            ->method('getMediaIdentifier')
            ->willReturn('https_2_host_1_ontologies_1_tao_0_rdf_3_i123456789abcdef0123456789abcdef01');

        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01');
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaSubclass,
            ]);

        $this->taoMediaResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('taomedia://asset/1')
            ->willReturn($asset);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01')
            ->willReturn($mediaResource);

        $this->mediaSubclass
            ->expects($this->once())
            ->method('countInstances')
            ->willReturn(1);
        $this->mediaSubclass
            ->expects($this->atLeastOnce())
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#subclass');

        $this->mediaClassSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->mediaSubclass)
            ->willReturn(true);

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($this->mediaSubclass);

        $this->sut->handleQtiTestDeletedEvent($this->event);
    }

    public function testEventReferencingNonMediaResourcesTriggersNoDeletions(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getReferencedResources')
            ->willReturn([
                'taomedia://asset/1',
            ]);

        $asset = $this->createMock(MediaAsset::class);
        $asset
            ->expects($this->once())
            ->method('getMediaIdentifier')
            ->willReturn('https_2_host_1_ontologies_1_tao_0_rdf_3_i123456789abcdef0123456789abcdef01');

        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01');
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaType,
            ]);

        $this->taoMediaResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('taomedia://asset/1')
            ->willReturn($asset);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01')
            ->willReturn($mediaResource);

        $this->mediaType
            ->expects($this->never())
            ->method('countInstances');
        $this->mediaType
            ->expects($this->never())
            ->method('getUri');

        $this->mediaClassSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->mediaType)
            ->willReturn(false);

        $this->mediaService
            ->expects($this->never())
            ->method('deleteResource');

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->handleQtiTestDeletedEvent($this->event);
    }

    public function testEventReferencingAssetInTheAssetsRootSkipsClassDeletion(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getReferencedResources')
            ->willReturn([
                'taomedia://asset/1',
            ]);

        $asset = $this->createMock(MediaAsset::class);
        $asset
            ->expects($this->once())
            ->method('getMediaIdentifier')
            ->willReturn('https_2_host_1_ontologies_1_tao_0_rdf_3_i123456789abcdef0123456789abcdef01');

        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01');
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaType,
            ]);

        $this->taoMediaResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('taomedia://asset/1')
            ->willReturn($asset);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01')
            ->willReturn($mediaResource);

        $this->mediaType
            ->expects($this->atLeastOnce())
            ->method('countInstances')
            ->willReturn(1);
        $this->mediaType
            ->expects($this->atLeastOnce())
            ->method('getUri')
            ->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $this->mediaClassSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->mediaType)
            ->willReturn(true);

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->handleQtiTestDeletedEvent($this->event);
    }

    public function testEventReferencingAssetWithSiblingsTriggersNoDeletions(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getReferencedResources')
            ->willReturn([
                'taomedia://asset/1',
            ]);

        $asset = $this->createMock(MediaAsset::class);
        $asset
            ->expects($this->once())
            ->method('getMediaIdentifier')
            ->willReturn('https_2_host_1_ontologies_1_tao_0_rdf_3_i123456789abcdef0123456789abcdef01');

        $mediaResource = $this->createMock(core_kernel_classes_Resource::class);
        $mediaResource
            ->method('getUri')
            ->willReturn('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01');
        $mediaResource
            ->expects($this->atLeastOnce())
            ->method('getTypes')
            ->willReturn([
                $this->mediaType,
            ]);

        $this->taoMediaResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('taomedia://asset/1')
            ->willReturn($asset);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with('https://host/ontologies/tao.rdf#i123456789abcdef0123456789abcdef01')
            ->willReturn($mediaResource);

        $this->mediaType
            ->expects($this->atLeastOnce())
            ->method('countInstances')
            ->willReturn(2);
        $this->mediaType
            ->method('getUri')
            ->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $this->mediaClassSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->mediaType)
            ->willReturn(true);

        $this->mediaService
            ->expects($this->once())
            ->method('deleteResource')
            ->with($mediaResource);

        $this->classDeleter
            ->expects($this->never())
            ->method('delete');

        $this->sut->handleQtiTestDeletedEvent($this->event);
    }
}
