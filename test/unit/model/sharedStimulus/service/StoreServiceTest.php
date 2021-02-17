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
 * Copyright (c) 2014-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use oat\generis\test\TestCase;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;

class StoreServiceTest extends TestCase
{
    /**
     * @var string
     */
    private $tempFilePath;

    public function setUp(): void
    {
        $this->tempFilePath = '/tmp/' . uniqid();
        file_put_contents($this->tempFilePath, '');
    }

    public function tearDown(): void
    {
        @unlink($this->tempFilePath);
    }

    public function testStoreSharedStimulus(): void
    {
        $fakeUniqueName = 'uniqueString';

        $fileSystemMock = $this->initFileSystemMock();
        $fileSystemMock->expects(self::once())
            ->method('createDir')
            ->with($fakeUniqueName);

        $fileSystemMock->expects(self::once())
            ->method('writeStream')
            ->willReturn(true);

        $stimulusStoreService = $this->getPreparedServiceInstance($fileSystemMock);
        $stimulusStoreService->expects(self::once())
            ->method('getUniqueName')
            ->willReturn($fakeUniqueName);

        $result = $stimulusStoreService->store($this->tempFilePath, 'dummyString', []);

        $this->assertEquals($result, $fakeUniqueName);
    }

    /**
     * @return FileSystem|MockObject
     */
    private function initFileSystemMock(): FileSystem
    {
        return $this->getMockBuilder(FileSystem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createDir', 'writeStream'])
            ->getMock();
    }

    /**
     * @return StoreService|MockObject
     */
    private function getPreparedServiceInstance(FileSystem $fileSystemMock): StoreService
    {
        $fileSystemServiceProphecy = $this->prophesize(FileSystemService::class);
        $fileSystemServiceProphecy->getFileSystem(Argument::any())->willReturn($fileSystemMock);

        $service = $this->getMockBuilder(StoreService::class)->onlyMethods(['getUniqueName'])->getMock();
        $service->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    FlySystemManagement::SERVICE_ID => $this->getMockBuilder(FlySystemManagement::class)->getMock(),
                    FileSystemService::SERVICE_ID => $fileSystemServiceProphecy->reveal()
                ]
            )
        );
        return $service;
    }
}
