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

namespace oat\taoMediaManager\test\unit\model\relation;

use oat\generis\test\ServiceManagerMockTrait;
use oat\tao\model\resources\relation\ResourceRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use PHPUnit\Framework\TestCase;
use oat\taoMediaManager\model\relation\MediaRelationService;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\tao\model\resources\relation\FindAllQuery;

class MediaRelationServiceTest extends TestCase
{
    use ServiceManagerMockTrait;

    public function testGetMediaRelation(): void
    {
        $id = 'id-fixture';
        $mediaRelationCollection = new MediaRelationCollection(...[
            new ResourceRelation('item', 'uri1'),
            new ResourceRelation('media', 'uri2'),
            new ResourceRelation('item', 'uri3'),
        ]);

        $mediaRelationRepository = $this->createMock(MediaRelationRepositoryInterface::class);
        $mediaRelationRepository
            ->method('findAll')
            ->with($this->callback(static fn (FindAllQuery $query) => $query->getSourceId() === $id))
            ->willReturn($mediaRelationCollection);

        $service = new MediaRelationService();
        $service->setServiceLocator($this->getServiceManagerMock([
            MediaRelationRepositoryInterface::SERVICE_ID => $mediaRelationRepository
        ]));

        $this->assertSame($mediaRelationCollection, $service->findRelations(new FindAllQuery($id)));
    }
}
