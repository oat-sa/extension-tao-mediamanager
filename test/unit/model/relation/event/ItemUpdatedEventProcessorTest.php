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
    private const MEDIA_LINK_1 = 'taomedia://mediamanager/https_2_test-tao-deploy_0_docker_0_localhost_1_ontologies_1_tao_0_rdf_3_i5ec293a38ebe623833180e3b0a547a6d4';
    private const MEDIA_LINK_2 = 'taomedia://mediamanager/https_2_test-tao-deploy_0_docker_0_localhost_1_ontologies_1_tao_0_rdf_3_i5ec293a38ebe623833180e3b0a547a6d5';
    private const MEDIA_LINK_3 = 'taomedia://mediamanager/https_2_test-tao-deploy_0_docker_0_localhost_1_ontologies_1_tao_0_rdf_3_i5ec293a38ebe623833180e3b0a547a6d6';

    private const MEDIA_LINK_1_PARSED = 'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5ec293a38ebe623833180e3b0a547a6d4';
    private const MEDIA_LINK_2_PARSED = 'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5ec293a38ebe623833180e3b0a547a6d5';
    private const MEDIA_LINK_3_PARSED = 'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5ec293a38ebe623833180e3b0a547a6d6';

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

    public function testProcess(): void
    {
        $this->updateService
            ->expects($this->once())
            ->method('updateByTargetId')
            ->with(
                'itemId',
                [
                    self::MEDIA_LINK_1_PARSED,
                    self::MEDIA_LINK_2_PARSED,
                    self::MEDIA_LINK_3_PARSED,
                ]
            );

        $this->idDiscoverService
            ->method('discover')
            ->willReturn(
                [
                    self::MEDIA_LINK_1_PARSED,
                    self::MEDIA_LINK_2_PARSED,
                    self::MEDIA_LINK_3_PARSED,
                ]
            );

        $this->subject->process(
            new ItemUpdatedEvent(
                'itemId',
                [
                    'includeElementReferences' => [
                        self::MEDIA_LINK_1
                    ],
                    'objectElementReferences' => [
                        self::MEDIA_LINK_2
                    ],
                    'imgElementReferences' => [
                        self::MEDIA_LINK_3
                    ]
                ]
            )
        );
    }

    public function testInvalidEventWillThrowException(): void
    {
        $this->expectException(InvalidEventException::class);

        $this->subject->process($this->createMock(Event::class));
    }
}
