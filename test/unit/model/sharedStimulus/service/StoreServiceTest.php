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

use PHPUnit\Framework\MockObject\MockObject;
use oat\generis\test\ServiceManagerMockTrait;
use PHPUnit\Framework\TestCase;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;

class StoreServiceTest extends TestCase
{
    use ServiceManagerMockTrait;

    private string $tempFilePath;

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
            ->method('createDirectory')
            ->with($fakeUniqueName);

        $fileSystemMock->expects(self::once())
            ->method('writeStream');
        $fileSystemMock->expects(self::once())
            ->method('directoryExists')
            ->willReturn(false);

        $stimulusStoreService = $this->getPreparedServiceInstance($fileSystemMock);
        $stimulusStoreService->expects(self::once())
            ->method('getUniqueName')
            ->willReturn($fakeUniqueName);

        $result = $stimulusStoreService->store($this->tempFilePath, 'dummyString', []);

        $this->assertEquals($result, $fakeUniqueName);
    }

    private function initFileSystemMock(): FileSystem|MockObject
    {
        return $this->getMockBuilder(FileSystem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createDirectory', 'writeStream', 'directoryExists'])
            ->getMock();
    }

    private function getPreparedServiceInstance(FileSystem $fileSystemMock): StoreService|MockObject
    {
        $fileSystemService = $this->createMock(FileSystemService::class);
        $fileSystemService->method('getFileSystem')->with($this->anything())->willReturn($fileSystemMock);

        $service = $this->getMockBuilder(StoreService::class)->onlyMethods(['getUniqueName'])->getMock();
        $service->setServiceLocator(
            $this->getServiceManagerMock(
                [
                    FlySystemManagement::SERVICE_ID => $this->getMockBuilder(FlySystemManagement::class)->getMock(),
                    FileSystemService::SERVICE_ID => $fileSystemService
                ]
            )
        );

        return $service;
    }
}
