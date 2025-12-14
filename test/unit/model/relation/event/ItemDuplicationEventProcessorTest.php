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

namespace oat\taoMediaManager\test\unit\model\relation\event;

use oat\taoItems\model\event\ItemDuplicatedEvent;
use oat\taoMediaManager\model\relation\event\processor\ItemDuplicationEventProcessor;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\service\ItemMediaCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemDuplicationEventProcessorTest extends TestCase
{
    private ItemMediaCollector|MockObject $itemMediaCollector;
    private MediaRelationRepositoryInterface|MockObject $mediaRelationRepository;
    private ItemDuplicationEventProcessor $subject;

    public function setUp(): void
    {
        $this->itemMediaCollector = $this->createMock(ItemMediaCollector::class);
        $this->mediaRelationRepository = $this->createMock(MediaRelationRepositoryInterface::class);

        $this->subject = new ItemDuplicationEventProcessor(
            $this->mediaRelationRepository,
            $this->itemMediaCollector
        );
    }

    public function testProcess(): void
    {
        $eventMock = $this->createMock(ItemDuplicatedEvent::class);
        $eventMock->method('jsonSerialize')->willReturn(
            [
                'itemUri' => 'itemUri',
                'cloneUri' => 'cloneUri'
            ]
        );

        $this->itemMediaCollector->expects($this->once())
            ->method('getItemMediaResources')
            ->with('itemUri')
            ->willReturn(['mediaUri1']);

        $expectedMediaRelation = new MediaRelation(MediaRelation::ITEM_TYPE, 'cloneUri');
        $expectedMediaRelation->withSourceId('mediaUri1');

        $this->mediaRelationRepository->expects($this->once())
            ->method('save')
            ->with($expectedMediaRelation);

        $this->subject->process($eventMock);
    }
}
