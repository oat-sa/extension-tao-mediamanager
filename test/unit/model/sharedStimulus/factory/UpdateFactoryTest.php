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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\factory;

use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\taoMediaManager\model\sharedStimulus\factory\UpdateFactory;
use oat\taoMediaManager\model\sharedStimulus\UpdateCommand;
use Psr\Http\Message\ServerRequestInterface;

class UpdateFactoryTest extends TestCase
{
    private const URI = 'uri';
    private const BODY = 'body';
    private const USER_ID = 'userid';

    /** @var UpdateFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new UpdateFactory();
    }

    public function testMakeGetCommandByRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')
            ->willReturn(
                json_encode(
                    [
                        'id' => self::URI,
                        'body' => self::BODY,
                    ]
                )
            );

        $user = $this->createMock(User::class);
        $user->method('getIdentifier')->willReturn(self::USER_ID);

        $this->assertEquals(
            new UpdateCommand(self::URI, self::BODY, self::USER_ID),
            $this->factory->patchStimulusByRequest($request, $user)
        );
    }
}
