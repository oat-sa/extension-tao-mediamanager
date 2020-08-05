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

use common_exception_MissingParameter;
use oat\generis\test\TestCase;
use oat\tao\model\task\migration\service\MigrationConfigFactory;
use oat\tao\model\task\migration\service\QueueMigrationService;
use oat\tao\model\task\migration\MigrationConfig;
use oat\tao\model\task\migration\service\ResultFilter;
use oat\tao\model\task\migration\service\ResultFilterFactory;
use oat\tao\model\task\migration\service\SpawnMigrationConfigService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoMediaManager\model\relation\service\ItemToMediaRdsSearcher;
use oat\taoMediaManager\model\relation\task\ItemToMediaRelationMigrationTask;
use oat\taoMediaManager\model\relation\task\ItemToMediaUnitProcessor;
use PHPUnit\Framework\MockObject\MockObject;

class ItemToMediaRelationMigrationTaskTest extends TestCase
{
    private const CHUNKSIZE_EXAMPLE = 1;
    private const START_EXAMPLE = 0;
    private const PICKSIZE_EXAMPLE = 2;
    private const REPEAT_EXAMPLE = true;

    /** @var ItemToMediaUnitProcessor|MockObject */
    private $processorMock;

    /** @var ItemToMediaRelationMigrationTask */
    private $subject;

    /** @var QueueMigrationService|MockObject */
    private $queueMigrationServiceMock;

    /** @var QueueDispatcherInterface|MockObject */
    private $queueDispatcherInterfaceMock;

    /** @var ItemToMediaRdsSearcher|MockObject */
    private $itemToMediaRdsSearcher;

    /** @var SpawnMigrationConfigService|MockObject */
    private $spawnMigrationConfigServiceMock;

    /** @var ResultFilterFactory|MockObject */
    private $resultFilterFactoryMock;

    /** @var MigrationConfigFactory|MockObject */
    private $migrationConfigFactoryMock;


    public function setUp(): void
    {
        $this->processorMock = $this->createMock(ItemToMediaUnitProcessor::class);
        $this->queueDispatcherInterfaceMock = $this->createMock(QueueDispatcherInterface::class);
        $this->queueMigrationServiceMock = $this->createMock(QueueMigrationService::class);
        $this->itemToMediaRdsSearcher = $this->createMock(ItemToMediaRdsSearcher::class);
        $this->spawnMigrationConfigServiceMock = $this->createMock(SpawnMigrationConfigService::class);
        $this->resultFilterFactoryMock = $this->createMock(ResultFilterFactory::class);
        $this->migrationConfigFactoryMock = $this->createMock(MigrationConfigFactory::class);
        $this->subject = new ItemToMediaRelationMigrationTask();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock([
                ItemToMediaUnitProcessor::class => $this->processorMock,
                QueueMigrationService::class => $this->queueMigrationServiceMock,
                ItemToMediaRdsSearcher::class => $this->itemToMediaRdsSearcher,
                QueueDispatcherInterface::SERVICE_ID => $this->queueDispatcherInterfaceMock,
                SpawnMigrationConfigService::class => $this->spawnMigrationConfigServiceMock,
                ResultFilterFactory::class => $this->resultFilterFactoryMock,
                MigrationConfigFactory::class => $this->migrationConfigFactoryMock,
            ]));
    }

    public function testInvokeWithRespawnConfig(): void
    {
        /** @var MigrationConfig|MockObject $respawnTaskConfig */
        $respawnTaskConfig = $this->createMock(MigrationConfig::class);
        $params['chunkSize'] = self::CHUNKSIZE_EXAMPLE;
        $params['start'] = self::START_EXAMPLE;
        $params['pickSize'] = self::PICKSIZE_EXAMPLE;
        $params['repeat'] = self::REPEAT_EXAMPLE;

        $this->queueMigrationServiceMock
            ->expects($this->once())
            ->method('migrate')
            ->willReturn($respawnTaskConfig);

        $respawnTaskConfig
            ->expects($this->exactly(2))
            ->method('getChunkSize');

        $respawnTaskConfig
            ->expects($this->once())
            ->method('getPickSize');

        $this->migrationConfigFactoryMock
            ->method('create')
            ->willReturn(new MigrationConfig(
                ['start' => 0],
                1,
                2,
                true
            ));

        $this->resultFilterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ResultFilter(['param1' => 1]));

        $callbackTaskInterface = $this->createMock(CallbackTaskInterface::class);
        $this->queueDispatcherInterfaceMock
            ->expects($this->once())
            ->method('createTask')
            ->willReturn($callbackTaskInterface);

        $this->subject->__invoke($params);
    }
}
