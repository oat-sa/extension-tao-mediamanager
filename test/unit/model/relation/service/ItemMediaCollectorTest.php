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
 * Copyright (c) 2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\relation\service;

use core_kernel_classes_Resource as Resource;
use oat\generis\model\data\Ontology;
use oat\taoMediaManager\model\relation\service\ItemMediaCollector;
use oat\taoQtiItem\model\qti\container\ContainerItemBody;
use oat\taoQtiItem\model\qti\Element;
use oat\taoQtiItem\model\qti\Item;
use oat\taoQtiItem\model\qti\Service as ItemsService;
use PHPUnit\Framework\TestCase;

class ItemMediaCollectorTest extends TestCase
{
    public function setUp(): void
    {
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->itemsServiceMock = $this->createMock(ItemsService::class);
        $this->subject = new ItemMediaCollector(
            $this->ontologyMock,
            $this->itemsServiceMock
        );
    }

    public function testGetItemMediaResources(): void
    {
        $resourceMock = $this->createMock(Resource::class);
        $itemMock = $this->createMock(Item::class);
        $bodyMock = $this->createMock(ContainerItemBody::class);
        $elementMock = $this->createMock(Element::class);


        $this->ontologyMock->expects($this->once())
            ->method('getResource')
            ->with('itemUri')
            ->willReturn($resourceMock);

        $this->itemsServiceMock->expects($this->once())
            ->method('getDataItemByRdfItem')
            ->with($resourceMock)
            ->willReturn($itemMock);

        $itemMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyMock);

        $bodyMock->expects($this->exactly(2))
            ->method('getComposingElements')
            ->willReturn([$elementMock]);

        $elementMock->expects($this->exactly(2))
            ->method('getAttributeValue')
            ->willReturn('taomedia://mediamanager/http_some_decoded_uri');

        $this->assertEquals(
            ['http_some_decoded_uri', 'http_some_decoded_uri'],
            $this->subject->getItemMediaResources('itemUri')
        );
    }
}
