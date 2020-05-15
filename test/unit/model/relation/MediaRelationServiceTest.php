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

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\MediaRelationService;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use Prophecy\Argument;

class MediaRelationServiceTest extends TestCase
{
    public function testGetMediaRelation()
    {
        $id = 'id-fixture';
        $mediaRelationCollection = new MediaRelationCollection(...[
            new MediaRelation('item', 'uri1'),
            new MediaRelation('media', 'uri2'),
            new MediaRelation('item', 'uri3'),
        ]);

        $repositoryProphecy = $this->prophesize(MediaRelationRepositoryInterface::class);
        $repositoryProphecy
            ->findAll(Argument::that(function (FindAllQuery $query) use ($id) {
                return $query->getMediaId() == $id;
            }))
            ->willReturn($mediaRelationCollection);

        $service = new MediaRelationService();
        $service->setServiceLocator($this->getServiceLocatorMock([
            MediaRelationRepositoryInterface::SERVICE_ID => $repositoryProphecy->reveal()
        ]));

        $this->assertSame($mediaRelationCollection, $service->getMediaRelation($id));
    }
}
