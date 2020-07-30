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
use oat\tao\model\task\migration\service\QueueMigrationService;
use oat\taoMediaManager\model\relation\task\ItemToMediaRelationMigrationTask;
use oat\taoMediaManager\model\relation\task\ItemToMediaUnitProcessor;
use PHPUnit\Framework\MockObject\MockObject;

class ItemToMediaRelationMigrationTaskTest extends TestCase
{
    private const CHUNKSIZE_EXAMPLE = 1;
    private const START_EXAMPLE = 0;
    private const PICKSIZE_EXAMPLE = 2;
    private const REPEAT_EXAMPLE = true;

    /** @var ItemToMediaUnitProcessor */
    private $processor;

    /** @var ItemToMediaRelationMigrationTask */
    private $subject;
    /** @var QueueMigrationService|MockObject */
    private $queueMigrationServiceMock;

    public function setUp(): void
    {
        $this->processor = $this->createMock(ItemToMediaUnitProcessor::class);
        $this->queueMigrationServiceMock = $this->createMock(QueueMigrationService::class);
        $this->subject = new ItemToMediaRelationMigrationTask();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock([
                ItemToMediaUnitProcessor::class => $this->processor,
                QueueMigrationService::class => $this->queueMigrationServiceMock
            ]));
    }

    public function testGetUnitProcessorWithMissingParams(): void
    {
        $params['chunkSize'] = self::CHUNKSIZE_EXAMPLE;
        $params['start'] = self::START_EXAMPLE;
        $params['pickSize'] = self::PICKSIZE_EXAMPLE;

        $this->expectException(common_exception_MissingParameter::class);

        $this->subject->__invoke($params);
    }
}
