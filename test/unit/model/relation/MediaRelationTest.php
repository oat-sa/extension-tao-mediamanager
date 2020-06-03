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

use InvalidArgumentException;
use JsonSerializable;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\relation\MediaRelation;

class MediaRelationTest extends TestCase
{
    public function testConstruct()
    {
        $mediaRelation = new MediaRelation(MediaRelation::ITEM_TYPE, 'uri-string', 'label');
        $this->assertSame(MediaRelation::ITEM_TYPE, $mediaRelation->getType());
        $this->assertSame('uri-string', $mediaRelation->getId());
        $this->assertSame('label', $mediaRelation->getLabel());
        $this->assertFalse($mediaRelation->isMedia());
    }

    public function testConstructWithDefaultLabel()
    {
        $mediaRelation = new MediaRelation(MediaRelation::MEDIA_TYPE, '24');
        $this->assertSame(MediaRelation::MEDIA_TYPE, $mediaRelation->getType());
        $this->assertSame('24', $mediaRelation->getId());
        $this->assertNull($mediaRelation->getLabel());
        $this->assertTrue($mediaRelation->isMedia());
    }

    public function testJsonSerialized()
    {
        $mediaRelation = new MediaRelation(MediaRelation::MEDIA_TYPE, '1', 'label');
        $this->assertInstanceOf(JsonSerializable::class, $mediaRelation);

        $expected = json_encode([
            'type' => MediaRelation::MEDIA_TYPE,
            'id' => '1',
            'label' => 'label',
        ]);

        $this->assertSame($expected, json_encode($mediaRelation));
    }

    public function testConstructExpectionWithWrongType()
    {
        $this->expectException(InvalidArgumentException::class);
        new MediaRelation('bad-example', '123');
    }
}
