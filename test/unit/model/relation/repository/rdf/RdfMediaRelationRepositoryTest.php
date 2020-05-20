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

namespace oat\taoMediaManager\test\unit\model\relation\repository\rdf;

use LogicException;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMapInterface;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use core_kernel_classes_Resource as RdfResource;
use PHPUnit\Framework\MockObject\MockObject;

class RdfMediaRelationRepositoryTest extends TestCase
{
    /** @var RdfMediaRelationRepository */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontology;

    /** @var RdfMediaRelationMapInterface|MockObject */
    private $itemMapper;

    /** @var RdfMediaRelationMapInterface|MockObject */
    private $mediaMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ontology = $this->createMock(Ontology::class);
        $this->itemMapper = $this->createMock(RdfMediaRelationMapInterface::class);
        $this->mediaMapper = $this->createMock(RdfMediaRelationMapInterface::class);
        $this->subject = new RdfMediaRelationRepository(
            [
                RdfMediaRelationRepository::MAP_OPTION => [
                    $this->itemMapper,
                    $this->mediaMapper,
                ]
            ]
        );
        $this->subject->setModel($this->ontology);
    }

    public function testFindAll(): void
    {
        $mediaId = 'fixture';

        $this->ontology
            ->method('getResource')
            ->with($mediaId)
            ->willReturn($this->createMock(RdfResource::class));

        $this->mediaMapper
            ->method('mapMediaRelations')
            ->willReturnCallback(
                function (RdfResource $mediaResource, MediaRelationCollection $mediaRelationCollection) {
                    $mediaRelationCollection->add(new MediaRelation('media', '1'));
                    $mediaRelationCollection->add(new MediaRelation('media', '2', 'media-2'));
                }
            );

        $this->mediaMapper
            ->method('createMediaRelation')
            ->willReturnCallback(
                function (RdfResource $mediaResource, string $sourceId) {
                    new MediaRelation('media', $mediaResource->getUri());
                }
            );

        $this->itemMapper
            ->method('mapMediaRelations')
            ->willReturnCallback(
                function (RdfResource $mediaResource, MediaRelationCollection $mediaRelationCollection) {
                    $mediaRelationCollection->add(new MediaRelation('item', '1'));
                    $mediaRelationCollection->add(new MediaRelation('item', '2', 'item-2'));
                }
            );

        $this->itemMapper
            ->method('createMediaRelation')
            ->willReturnCallback(
                function (RdfResource $mediaResource, string $sourceId) {
                    new MediaRelation('item', $mediaResource->getUri());
                }
            );

        $expected = [
            [
                'type' => 'item',
                'id' => '1',
                'label' => '',
            ],
            [
                'type' => 'item',
                'id' => '2',
                'label' => 'item-2',
            ],
            [
                'type' => 'media',
                'id' => '1',
                'label' => '',
            ],
            [
                'type' => 'media',
                'id' => '2',
                'label' => 'media-2',
            ],
        ];

        $result = $this->subject->findAll(new FindAllQuery($mediaId));

        $this->assertSame(json_encode($expected), json_encode(iterator_to_array($result->getIterator())));
    }

    public function testFindAllWithInvalidFilterWillThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid query filter');

        $this->subject->findAll(new FindAllQuery());
    }
}
