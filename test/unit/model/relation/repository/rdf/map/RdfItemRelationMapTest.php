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

namespace oat\taoMediaManager\test\unit\model\relation\repository\rdf\map;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfItemRelationMap;
use ReflectionMethod;

class RdfItemRelationMapTest extends TestCase
{
    public function testGetMediaRelationPropertyUri()
    {
        $map = new RdfItemRelationMap();
        $method = new ReflectionMethod($map, 'getMediaRelationPropertyUri');
        $method->setAccessible(true);
        $this->assertSame('http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem', $method->invoke($map));
    }

    public function testCreateMediaRelation()
    {
        $map = new RdfItemRelationMap();
        $method = new ReflectionMethod($map, 'createMediaRelation');
        $method->setAccessible(true);
        $mediaRelation = $method->invoke($map, 'id', 'label');
        $this->assertSame('item', $mediaRelation->getType());
        $this->assertSame('id', $mediaRelation->getId());
        $this->assertSame('label', $mediaRelation->getLabel());
    }
}
