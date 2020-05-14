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

use Exception;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_classes_Property as RdfProperty;
use Prophecy\Argument;

class RdfMediaRelationRepositoryTest extends TestCase
{
    /**
     * @dataProvider provideFindAllRelatedMedia
     * @param $relatedItems
     * @param $relatedAssets
     * @throws Exception
     */
    public function testFindAll($relatedItems,$relatedAssets)
    {
        $mediaId = 'uri-fixture';

        $modelProphecy = $this->prophesize(Ontology::class);
        $mediaResourceProphecy = $this->prophesize(RdfResource::class);

        $this->prophesizeGetPropertyValuesForItemRelation($modelProphecy, $mediaResourceProphecy, $relatedItems);
        $this->prophesizeGetPropertyValuesForAssetRelation($modelProphecy, $mediaResourceProphecy, $relatedAssets);

        $modelProphecy->getResource(Argument::exact($mediaId))->willReturn($mediaResourceProphecy->reveal());

        $repository = new RdfMediaRelationRepository();
        $repository->setModel($modelProphecy->reveal());
        $mediaCollection = $repository->findAll(new FindAllQuery($mediaId));

        $expected = array_values(array_merge($relatedItems, $relatedAssets));
        $result = iterator_to_array($mediaCollection->getIterator());

        $this->assertEquals(json_encode($expected), json_encode($result));
    }

    public function provideFindAllRelatedMedia()
    {
        return [
            // only related items
            [
                [
                    'item-uri-1' => new MediaRelation('item', 'item-uri-1', 'item-label-1'),
                    'item-uri-2' => new MediaRelation('item', 'item-uri-2', 'item-label-2'),
                ],
                [],

            ],
            // only related assets
            [
                [],
                [
                    'asset-uri-1' => new MediaRelation('asset', 'asset-uri-1', 'asset-label-1'),
                    'asset-uri-2' => new MediaRelation('asset', 'asset-uri-2', 'asset-label-2'),
                ]
            ],
            // with related items & assets
            [
                [
                    'item-uri-1' => new MediaRelation('item', 'item-uri-1', 'item-label-1'),
                    'item-uri-2' => new MediaRelation('item', 'item-uri-2', 'item-label-2'),
                ],
                [
                    'asset-uri-1' => new MediaRelation('asset', 'asset-uri-1', 'asset-label-1'),
                    'asset-uri-2' => new MediaRelation('asset', 'asset-uri-2', 'asset-label-2'),
                ]
            ],
            // no relation
            [
                [],
                []
            ],
        ];
    }

    protected function prophesizeGetPropertyValuesForItemRelation($modelProphecy, $mediaResourceProphecy, array $relatedItems)
    {
        $propertyUri = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem';
        $this->prophesizeGetPropertyValues($modelProphecy, $mediaResourceProphecy, $propertyUri, $relatedItems);
    }

    protected function prophesizeGetPropertyValuesForAssetRelation($modelProphecy, $mediaResourceProphecy, array $relatedAssets)
    {
        $propertyUri = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedAsset';
        $this->prophesizeGetPropertyValues($modelProphecy, $mediaResourceProphecy, $propertyUri, $relatedAssets);
    }

    protected function prophesizeGetPropertyValues($modelProphecy, $mediaResourceProphecy, $propertyUri, $values)
    {
        $property = new RdfProperty($propertyUri);
        $modelProphecy->getProperty(Argument::exact($propertyUri))->willReturn($property);

        $mediaResourceProphecy->getPropertyValues(Argument::exact($property))->willReturn(array_keys($values));

        foreach ($values as $mediaRelation) {
            $relationMediaProphecy = $this->prophesize(RdfResource::class);
            $relationMediaProphecy->getUri()->willReturn($mediaRelation->getId());
            $relationMediaProphecy->getLabel()->willReturn($mediaRelation->getLabel());
            $modelProphecy->getResource(Argument::exact($mediaRelation->getId()))->willReturn($relationMediaProphecy->reveal())->shouldBeCalled();
        }
    }
}
