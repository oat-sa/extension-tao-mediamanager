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

use oat\tao\model\resources\Event\InstanceCopiedEvent;
use oat\taoMediaManager\model\relation\event\processor\EventInstanceCopiedProcessor;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\service\ItemMediaCollector;
use PHPUnit\Framework\TestCase;

class EventInstanceCopiedProcessorTest extends TestCase
{
    public function setUp(): void
    {
        $this->itemMediaCollector = $this->createMock(ItemMediaCollector::class);
        $this->mediaRelationRepository = $this->createMock(MediaRelationRepositoryInterface::class);

        $this->eventInstanceCopiedProcessor = new EventInstanceCopiedProcessor(
            $this->mediaRelationRepository,
            $this->itemMediaCollector
        );
    }

    public function testEvent(): void
    {
        $eventMock = $this->createMock(InstanceCopiedEvent::class);
        $eventMock->method('getOriginInstanceUri')->willReturn('originInstanceUri');
        $eventMock->method('getInstanceUri')->willReturn('instanceUri');

        $this->itemMediaCollector->expects($this->once())
            ->method('getItemMediaResources')
            ->with('originInstanceUri')
            ->willReturn(['mediaUri1']);

        $this->eventInstanceCopiedProcessor->process($eventMock);
    }
}
