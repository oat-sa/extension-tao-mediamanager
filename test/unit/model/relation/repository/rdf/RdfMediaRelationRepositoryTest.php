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
use oat\taoMediaManager\model\relation\repository\query\FindAllByTargetQuery;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_classes_Property as RdfProperty;
use PHPUnit\Framework\MockObject\MockObject;

class RdfMediaRelationRepositoryTest extends TestCase
{
    private const ITEM_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem';
    private const MEDIA_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedMedia';

    /** @var RdfMediaRelationRepository */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontology;

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
        $this->subject = new RdfMediaRelationRepository();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontology,
                    ComplexSearchService::SERVICE_ID => $this->complexSearch,
                ]
            )
        );
    }

    public function testFindAllByMediaId(): void
    {
        $mediaId = 'fixture';

        $itemRelationProperty = $this->createConfiguredMock(RdfProperty::class, []);
        $mediaRelationProperty = $this->createConfiguredMock(RdfProperty::class, []);

        $relatedItem1 = $this->createConfiguredMock(RdfResource::class, ['getUri' => '1', 'getLabel' => '']);
        $relatedItem2 = $this->createConfiguredMock(RdfResource::class, ['getUri' => '2', 'getLabel' => 'item-2']);
        $relatedMedia1 = $this->createConfiguredMock(RdfResource::class, ['getUri' => '1', 'getLabel' => '']);
        $relatedMedia2 = $this->createConfiguredMock(RdfResource::class, ['getUri' => '2', 'getLabel' => 'media-2']);

        $resource = $this->createMock(RdfResource::class);

        $resource
            ->method('getPropertiesValues')
            ->with([
                $itemRelationProperty, $mediaRelationProperty
            ])
            ->willReturn([
                self::ITEM_RELATION_PROPERTY => [
                    $relatedItem1,
                    $relatedItem2,
                ],
                self::MEDIA_RELATION_PROPERTY => [
                    $relatedMedia1,
                    $relatedMedia2,
                ]
            ]);

        $this->ontology
            ->method('getResource')
            ->with($mediaId)
            ->willReturn($resource);

        $this->ontology
            ->method('getProperty')
            ->withConsecutive(
                [$this->equalTo(self::ITEM_RELATION_PROPERTY)],
                [$this->equalTo(self::MEDIA_RELATION_PROPERTY)]
            )
            ->willReturnOnConsecutiveCalls(
                $itemRelationProperty,
                $mediaRelationProperty
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
            (object) ['subject' => '1'],
            (object) ['subject' => '2'],
        ];

        $this->query
            ->method('add')
            ->with(self::ITEM_RELATION_PROPERTY)
            ->method('equals')
            ->with($itemId);

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

        $result = $this->subject->findAllByTarget(new FindAllByTargetQuery($itemId, MediaRelation::MEDIA_TYPE));

        $this->assertSame(json_encode($expected), json_encode(iterator_to_array($result->getIterator())));
    }
}
