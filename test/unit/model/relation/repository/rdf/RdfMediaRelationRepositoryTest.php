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
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\test\TestCase;
use oat\search\Query;
use oat\search\base\SearchGateWayInterface;
use oat\search\QueryBuilder;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMap;
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

    /** @var ComplexSearchService|MockObject */
    private $complexSearch;

    /** @var Query|MockObject */
    private $query;

    /** @var QueryBuilder|MockObject */
    private $queryBuilder;

    /** @var SearchGateWayInterface|MockObject */
    private $searchGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complexSearch = $this->createMock(ComplexSearchService::class);
        $this->query = $this->createMock(Query::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->searchGateway = $this->createMock(SearchGateWayInterface::class);
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
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontology,
                    ComplexSearchService::SERVICE_ID => $this->complexSearch,
                    RdfMediaRelationMap::class => $this->mediaMapper,
                ]
            )
        );
    }

    public function testFindAllByMediaId(): void
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

        $this->itemMapper
            ->method('mapMediaRelations')
            ->willReturnCallback(
                function (RdfResource $mediaResource, MediaRelationCollection $mediaRelationCollection) {
                    $mediaRelationCollection->add(new MediaRelation('item', '1'));
                    $mediaRelationCollection->add(new MediaRelation('item', '2', 'item-2'));
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

    public function testFindAllByItemId(): void
    {
        $itemId = 'itemId';

        $this->mediaMapper
            ->method('createMediaRelation')
            ->willReturnOnConsecutiveCalls(
                ...[
                    (new MediaRelation('media', '1'))->withSourceId('item1'),
                    (new MediaRelation('media', '2'))->withSourceId('item1'),

                ]
            );

        $expected = [
            [
                'type' => 'media',
                'id' => '1',
                'label' => '',
            ],
            [
                'type' => 'media',
                'id' => '2',
                'label' => '',
            ],
        ];

        $result = [
            $this->createMock(RdfResource::class),
            $this->createMock(RdfResource::class),
        ];

        $this->complexSearch
            ->method('query')
            ->willReturn($this->queryBuilder);

        $this->complexSearch
            ->method('getGateway')
            ->willReturn($this->searchGateway);

        $this->complexSearch
            ->method('searchType')
            ->willReturn($this->query);

        $this->searchGateway
            ->method('search')
            ->willReturn($result);

        $this->query
            ->method('add')
            ->willReturn($this->query);

        $this->query
            ->method('__call')
            ->with('equals')
            ->willReturn($this->query);

        $result = $this->subject->findAll(new FindAllQuery(null, $itemId));

        $this->assertSame(json_encode($expected), json_encode(iterator_to_array($result->getIterator())));
    }

    public function testFindAllWithInvalidFilterWillThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid query filter');

        $this->subject->findAll(new FindAllQuery());
    }
}
