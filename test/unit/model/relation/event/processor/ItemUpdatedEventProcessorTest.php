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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\relation\event\processor;

use oat\generis\test\TestCase;
use oat\oatbox\event\Event;
use oat\taoItems\model\event\ItemUpdatedEvent;
use oat\taoMediaManager\model\relation\event\processor\InvalidEventException;
use oat\taoMediaManager\model\relation\event\processor\ItemUpdatedEventProcessor;
use oat\taoMediaManager\model\relation\service\IdDiscoverService;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;
use PHPUnit\Framework\MockObject\MockObject;

class ItemUpdatedEventProcessorTest extends TestCase
{
    private const ITEM_URI = 'itemUri';
    private const IMG_REFERENCE = 'taomedia://mediamanager/img-reference';
    private const STYLESHEET_REFERENCE = 'taomedia://mediamanager/stylesheet-reference';
    private const IMG_ID = 'imgId';
    private const STYLESHEET_ID = 'stylesheetId';

    /** @var ItemUpdatedEventProcessor */
    private $subject;

    /** @var ItemRelationUpdateService|MockObject */
    private $updateService;

    /** @var IdDiscoverService|MockObject */
    private $idDiscoverService;

    public function setUp(): void
    {
        $this->idDiscoverService = $this->createMock(IdDiscoverService::class);
        $this->updateService = $this->createMock(ItemRelationUpdateService::class);
        $this->subject = new ItemUpdatedEventProcessor();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ItemRelationUpdateService::class => $this->updateService,
                    IdDiscoverService::class => $this->idDiscoverService,
                ]
            )
        );
    }

    public function testProcessAggregatesStylesheetReferencesWhenElementReferencesArePresent(): void
    {
        $this->idDiscoverService->expects($this->once())
            ->method('discover')
            ->with([self::IMG_REFERENCE, self::STYLESHEET_REFERENCE])
            ->willReturn([self::IMG_ID, self::STYLESHEET_ID]);

        $this->updateService->expects($this->once())
            ->method('updateByTargetId')
            ->with(self::ITEM_URI, [self::IMG_ID, self::STYLESHEET_ID]);

        $this->subject->process(
            new ItemUpdatedEvent(
                self::ITEM_URI,
                [
                    'imgElementReferences' => [self::IMG_REFERENCE],
                    'stylesheetElementReferences' => [self::STYLESHEET_REFERENCE],
                ]
            )
        );
    }

    public function testProcessIgnoresStylesheetOnlyPayload(): void
    {
        $this->idDiscoverService->expects($this->never())
            ->method('discover');

        $this->updateService->expects($this->never())
            ->method('updateByTargetId');

        $this->subject->process(
            new ItemUpdatedEvent(
                self::ITEM_URI,
                [
                    'stylesheetElementReferences' => [self::STYLESHEET_REFERENCE],
                ]
            )
        );
    }

    public function testProcessIgnoresPayloadWithoutKnownReferenceKeys(): void
    {
        $this->idDiscoverService->expects($this->never())
            ->method('discover');

        $this->updateService->expects($this->never())
            ->method('updateByTargetId');

        $this->subject->process(
            new ItemUpdatedEvent(
                self::ITEM_URI,
                [
                    'unrelatedKey' => [self::IMG_REFERENCE],
                ]
            )
        );
    }

    public function testProcessRejectsInvalidEvent(): void
    {
        $this->expectException(InvalidEventException::class);

        $this->subject->process($this->createMock(Event::class));
    }
}
