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

namespace oat\taoMediaManager\test\unit\model\relation\service\remove;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\service\remove\MediaRelationRemoveService;
use PHPUnit\Framework\MockObject\MockObject;

class MediaRelationRemoveServiceTest extends TestCase
{
    /** @var MediaRelationRemoveService */
    private $subject;

    /** @var MediaRelationRepositoryInterface|MockObject */
    private $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(MediaRelationRepositoryInterface::class);
        $this->subject = new MediaRelationRemoveService();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    MediaRelationRepositoryInterface::SERVICE_ID => $this->repository,
                ]
            )
        );
    }

    public function testRemoveMediaRelations(): void
    {
        $sourceId = 'mediaId';
        $media1 = new MediaRelation(MediaRelation::MEDIA_TYPE, 'media1');
        $media2 = new MediaRelation(MediaRelation::MEDIA_TYPE, 'media2');

        $collection = new MediaRelationCollection(
            ...[
                $media1,
                $media2,
            ]
        );

        $this->repository
            ->expects($this->once())
            ->method('findAllByTarget')
            ->willReturn($collection);

        $this->repository
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                ...[
                    [
                        $media1->withSourceId($sourceId),
                    ],
                    [
                        $media2->withSourceId($sourceId),
                    ]
                ]
            );

        $this->assertNull($this->subject->removeMediaRelations($sourceId));
    }
}
