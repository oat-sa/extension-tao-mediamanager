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

namespace oat\taoMediaManager\test\unit\model\relation\event;

use Exception;
use oat\generis\test\TestCase;
use oat\oatbox\event\Event;
use oat\oatbox\log\LoggerService;
use oat\taoMediaManager\model\relation\event\MediaRelationListener;
use oat\taoMediaManager\model\relation\event\processor\ItemRemovedProcessor;
use oat\taoMediaManager\model\relation\event\processor\ItemUpdatedProcessor;
use oat\taoMediaManager\model\relation\event\processor\MediaRemovedProcessor;
use oat\taoMediaManager\model\relation\event\processor\ProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;

class MediaRelationListenerTest extends TestCase
{
    /** @var MediaRelationListener */
    private $subject;

    /** @var ProcessorInterface|MockObject */
    private $processor;

    /** @var Event|MockObject */
    private $event;

    /** @var LoggerService|MockObject */
    private $logger;

    public function setUp(): void
    {
        $this->event = $this->createMock(Event::class);
        $this->processor = $this->createMock(ProcessorInterface::class);
        $this->logger = $this->createMock(LoggerService::class);
        $this->subject = new MediaRelationListener();
        $this->subject->setLogger($this->logger);
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ItemUpdatedProcessor::class => $this->processor,
                    ItemRemovedProcessor::class => $this->processor,
                    MediaRemovedProcessor::class => $this->processor,
                ]
            )
        );
    }

    public function testWhenItemIsUpdated(): void
    {
        $this->expectEventToBeProcessed();

        $this->assertNull($this->subject->whenItemIsUpdated($this->event));
    }

    public function testWhenItemIsRemoved(): void
    {
        $this->expectEventToBeProcessed();

        $this->assertNull($this->subject->whenItemIsRemoved($this->event));
    }

    public function testWhenMediaIsRemoved(): void
    {
        $this->expectEventToBeProcessed();

        $this->assertNull($this->subject->whenMediaIsRemoved($this->event));
    }

    public function testLogProcessException(): void
    {
        $this->processor
            ->method('process')
            ->willThrowException(new Exception('Error'));

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->assertNull($this->subject->whenMediaIsRemoved($this->event));
    }

    private function expectEventToBeProcessed(): void
    {
        $this->processor
            ->method('process')
            ->with($this->event);

        $this->logger
            ->expects($this->exactly(2))
            ->method('debug');
    }
}
