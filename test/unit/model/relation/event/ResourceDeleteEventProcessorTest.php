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

use oat\generis\model\data\event\ResourceDeleted;
use oat\tao\model\TaoOntology;
use oat\taoMediaManager\model\relation\event\processor\ResourceDeleteEventProcessor;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResourceDeleteEventProcessorTest extends TestCase
{
    private ItemRelationUpdateService|MockObject $itemRelationUpdateServiceMock;
    private ResourceDeleteEventProcessor $subject;

    public function setUp(): void
    {
        $this->itemRelationUpdateServiceMock = $this->createMock(ItemRelationUpdateService::class);

        $this->subject = new ResourceDeleteEventProcessor(
            $this->itemRelationUpdateServiceMock
        );
    }

    public function testProcess(): void
    {
        $eventMock = $this->createMock(ResourceDeleted::class);
        $eventMock->method('getResourceType')->willReturn(TaoOntology::CLASS_URI_ITEM);
        $eventMock->method('getId')->willReturn('ItemId');
        $this->itemRelationUpdateServiceMock->expects(self::once())
            ->method('updateByTargetId')
            ->with('ItemId');

        $this->subject->process($eventMock);
    }
}
