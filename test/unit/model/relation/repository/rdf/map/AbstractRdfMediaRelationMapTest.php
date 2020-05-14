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

use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\rdf\map\AbstractRdfMediaRelationMap;
use Prophecy\Argument;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_classes_Property as RdfProperty;

class AbstractRdfMediaRelationMapTest extends TestCase
{
    public function testGetMediaRelations()
    {
        $values =[
            'asset-uri-1' => new MediaRelation('media', 'asset-uri-1', 'asset-label-1'),
            'asset-uri-2' => new MediaRelation('media', 'asset-uri-2', 'asset-label-2'),
        ];

        $modelProphecy = $this->prophesize(Ontology::class);

        $property = new RdfProperty('uri-fixture');
        $modelProphecy->getProperty(Argument::exact('uri-fixture'))->willReturn($property);

        $mediaResourceProphecy = $this->prophesize(RdfResource::class);
        $mediaResourceProphecy->getPropertyValues(Argument::exact($property))->willReturn(array_keys($values));

        foreach ($values as $mediaRelation) {
            $relationMediaProphecy = $this->prophesize(RdfResource::class);
            $relationMediaProphecy->getUri()->willReturn($mediaRelation->getId());
            $relationMediaProphecy->getLabel()->willReturn($mediaRelation->getLabel());
            $modelProphecy->getResource(Argument::exact($mediaRelation->getId()))->willReturn($relationMediaProphecy->reveal())->shouldBeCalled();
        }

        $mediaRelationCollection = new MediaRelationCollection();

        $map = $this->getAbstractMap();
        $map->setModel($modelProphecy->reveal());
        $map->getMediaRelations($mediaResourceProphecy->reveal(), $mediaRelationCollection);

        $expected = array_values($values);
        $result = iterator_to_array($mediaRelationCollection->getIterator());

        $this->assertEquals(json_encode($expected), json_encode($result));
    }

    private function getAbstractMap(): AbstractRdfMediaRelationMap
    {
        return new class extends AbstractRdfMediaRelationMap
        {
            protected function getMediaRelationPropertyUri(): string
            {
                return 'uri-fixture';
            }

            protected function createMediaRelation(string $uri, string $label): MediaRelation
            {
                return new MediaRelation('media', $uri, $label);
            }
        };
    }

}