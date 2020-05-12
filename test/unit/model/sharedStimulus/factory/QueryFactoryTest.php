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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\factory;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\sharedStimulus\factory\QueryFactory;
use oat\taoMediaManager\model\sharedStimulus\FindQuery;
use Psr\Http\Message\ServerRequestInterface;

class QueryFactoryTest extends TestCase
{
    private const URI = 'uri';

    /** @var QueryFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new QueryFactory();
    }

    public function testMakeGetCommandByRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')
            ->willReturn(
                [
                    'id' => self::URI
                ]
            );

        $this->assertEquals(new FindQuery(self::URI), $this->factory->makeFindQueryByRequest($request));
    }
}
