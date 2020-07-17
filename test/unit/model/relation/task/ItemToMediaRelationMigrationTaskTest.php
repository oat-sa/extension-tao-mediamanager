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

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\task\ItemToMediaRelationMigrationTask;
use oat\taoMediaManager\model\relation\task\ItemToMediaUnitProcessor;
use ReflectionMethod;

class ItemToMediaRelationMigrationTaskTest extends TestCase
{
    /** @var ItemToMediaUnitProcessor */
    private $processor;

    /** @var ItemToMediaRelationMigrationTask */
    private $subject;

    public function setUp(): void
    {
        $this->processor = $this->createMock(ItemToMediaUnitProcessor::class);
        $this->subject = $this->getMockForAbstractClass(ItemToMediaRelationMigrationTask::class);
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ItemToMediaUnitProcessor::class => $this->processor,
                ]
            )
        );
    }

    public function testGetUnitProcessor(): void
    {
        $reflectionMethod = new ReflectionMethod($this->subject, 'getUnitProcessor');
        $reflectionMethod->setAccessible(true);

        $this->assertSame($this->processor, $reflectionMethod->invoke($this->subject));
    }
}
