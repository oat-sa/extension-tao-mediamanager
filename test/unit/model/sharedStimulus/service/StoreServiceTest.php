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

namespace oat\taoMediaManager\test\integration\model;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;

class StoreServiceTest extends TestCase
{
    private $tempFilePath = null;

    public function setUp(): void
    {
        $this->tempFilePath = '/tmp/' . uniqid();
        file_put_contents($this->tempFilePath, '');
    }

    public function tearDown(): void
    {
        @unlink($this->tempFilePath);
    }

    public function testStoreShareStimulus()
    {
        $fakeUniqueName = 'uniqueString';

        $fileManagementMock = $this->initFileManagementMock();
        $fileManagementMock->expects(self::once())
            ->method('createDir')
            ->with($fakeUniqueName);

        $fileManagementMock->expects(self::once())
            ->method('writeStream')
            ->willReturn(true);

        $stimulusStoreService = $this->getPreparedServiceInstance($fileManagementMock);
        $stimulusStoreService->expects(self::once())
            ->method('getUniqueName')
            ->willReturn($fakeUniqueName);

        $result = $stimulusStoreService->store($this->tempFilePath, 'dummyString', []);

        $this->assertEquals($result, $fakeUniqueName);
    }

    private function initFileManagementMock()
    {
        return $this->getMockBuilder(FlySystemManagement::class)
            ->onlyMethods(['createDir', 'writeStream'])
            ->getMock();
    }

    private function getPreparedServiceInstance($fileManagementMock)
    {
        $service = $this->getMockBuilder(StoreService::class)->onlyMethods(['getUniqueName'])->getMock();
        $service->setServiceLocator(
            $this->getServiceLocatorMock([FlySystemManagement::SERVICE_ID => $fileManagementMock,])
        );
        return $service;
    }
}
