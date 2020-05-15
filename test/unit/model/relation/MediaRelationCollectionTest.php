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

class MediaRelationCollectionTest extends TestCase
{
    public function testConstructAndGetIterator()
    {
        $fixtures = [
            new MediaRelation('item', 'uri1', 'label1'),
            new MediaRelation('media', 'uri2')
        ];

        $collection = new MediaRelationCollection(...$fixtures);
        $this->assertSame($fixtures, iterator_to_array($collection->getIterator()));
    }

    public function testConstructAndJsonEncode()
    {
        $fixtures = [
            new MediaRelation('item', 'uri1', 'label1'),
            new MediaRelation('media', 'uri2')
        ];

        $collection = new MediaRelationCollection(...$fixtures);
        $this->assertSame(json_encode($fixtures), json_encode($collection));
    }

    public function testAdd()
    {
        $addFixture1 = new MediaRelation('item', 'uri1', 'label1');
        $addFixture2 = new MediaRelation('media', 'uri2');
        $addFixture3 = new MediaRelation('media', 'uri3', 'toto');
        $expected = [
            $addFixture1, $addFixture2, $addFixture3
        ];

        $collection = (new MediaRelationCollection(...[$addFixture1]));
        $collection->add($addFixture2)->add($addFixture3);

        $this->assertSame(json_encode($expected), json_encode($collection));
    }
}
