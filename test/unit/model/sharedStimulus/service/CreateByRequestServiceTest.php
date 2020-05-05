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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use FileNotFoundException;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\service\CreateByRequestService;
use oat\taoMediaManager\model\sharedStimulus\service\CreateService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

class CreateByRequestServiceTest extends TestCase
{
    private const URI = 'uri';
    private const CLASS_URI = 'uri';
    private const LANGUAGE_URI = 'uri';
    private const NAME = 'name';

    /** @var CreateByRequestService */
    private $createByRequestService;

    /** @var CreateService|MockObject */
    private $createService;

    public function setUp(): void
    {
        $this->createService = $this->createMock(CreateService::class);
        $this->createByRequestService = new CreateByRequestService();
        $this->createByRequestService->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    CreateService::class => $this->createService,
                ]
            )
        );
    }

    public function testCreateSharedStimulusByRequest(): void
    {
        $command = new CreateCommand(
            self::CLASS_URI,
            self::NAME,
            self::LANGUAGE_URI
        );

        $expectedSharedStimulus = new SharedStimulus(
            self::URI,
            self::NAME,
            self::LANGUAGE_URI
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')
            ->willReturn(
                json_encode(
                    [
                        'classUri' => self::CLASS_URI,
                        'languageUri' => self::LANGUAGE_URI,
                        'name' => self::NAME,
                    ]
                )
            );

        $this->createService->method('create')
            ->with($command)
            ->willReturn($expectedSharedStimulus);

        $this->assertEquals($expectedSharedStimulus, $this->createByRequestService->create($request));
    }

    public function testCannotCreateSharedStimulusByRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')
            ->willReturn('[]');

        $this->createService->method('create')
            ->willThrowException(new FileNotFoundException('File Not Found'));

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File Not Found');

        $this->createByRequestService->create($request);
    }
}
