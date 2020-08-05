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

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\tao\model\task\migration\ResultUnit;
use oat\taoMediaManager\model\relation\service\IdDiscoverService;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;
use oat\taoMediaManager\model\relation\task\ItemToMediaUnitProcessor;
use oat\taoQtiItem\model\qti\ElementReferences;
use oat\taoQtiItem\model\qti\Item;
use oat\taoQtiItem\model\qti\parser\ElementReferencesExtractor;
use oat\taoQtiItem\model\qti\Service;
use PHPUnit\Framework\MockObject\MockObject;

class ItemToMediaUnitProcessorTest extends TestCase
{
    /** @var ItemToMediaUnitProcessor */
    private $subject;

    /** @var ItemRelationUpdateService|MockObject */
    private $itemRelationUpdateService;

    /** @var ElementReferencesExtractor|MockObject */
    private $elementReferencesExtractor;

    /** @var Service|MockObject */
    private $qtiService;

    /** @var IdDiscoverService|MockObject */
    private $idDiscoverService;

    /** @var Ontology|MockObject */
    private $ontology;

    public function setUp(): void
    {
        $this->itemRelationUpdateService = $this->createMock(ItemRelationUpdateService::class);
        $this->elementReferencesExtractor = $this->createMock(ElementReferencesExtractor::class);
        $this->qtiService = $this->createMock(Service::class);
        $this->idDiscoverService = $this->createMock(IdDiscoverService::class);
        $this->ontology = $this->createMock(Ontology::class);
        $this->subject = new ItemToMediaUnitProcessor();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ItemRelationUpdateService::class => $this->itemRelationUpdateService,
                    ElementReferencesExtractor::class => $this->elementReferencesExtractor,
                    Service::class => $this->qtiService,
                    IdDiscoverService::class => $this->idDiscoverService,
                    Ontology::SERVICE_ID => $this->ontology,
                ]
            )
        );
    }

    public function testProcess(): void
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);
        $qtiItem = $this->createMock(Item::class);
        $references = $this->createMock(ElementReferences::class);
        $allReferences = ['ref'];
        $referenceIds = ['id'];
        $uri = 'abc123';

        $references->method('getAllReferences')
            ->willReturn($allReferences);

        $this->ontology
            ->method('getResource')
            ->with($uri)
            ->willReturn($resource);

        $this->qtiService
            ->method('getDataItemByRdfItem')
            ->with($resource)
            ->willReturn($qtiItem);

        $this->elementReferencesExtractor
            ->method('extractAll')
            ->with($qtiItem)
            ->willReturn($references);

        $this->idDiscoverService
            ->method('discover')
            ->with($allReferences)
            ->willReturn($referenceIds);

        $this->itemRelationUpdateService
            ->method('updateByTargetId')
            ->with($uri, $referenceIds);

        $resource->method('getUri')->willReturn($uri);

        $this->assertNull($this->subject->process(new ResultUnit($resource)));
    }
}
