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

namespace oat\taoMediaManager\test\unit\model\export;

use core_kernel_classes_Resource;
use League\Flysystem\DirectoryListing;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoMediaManager\model\export\service\SharedStimulusCSSExporter;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use Prophecy\Argument;

class SharedStimulusCSSExporterTest extends TestCase
{
    /**
     * @var \ZipArchive
     */
    private $zipArchive;

    public function setUp(): void
    {
        $this->zipArchive = new \ZipArchive();
        $zipFile = '/tmp/' . uniqid();
        $this->zipArchive->open($zipFile, \ZipArchive::CREATE);
    }

    public function tearDown(): void
    {
        $zipFile = $this->zipArchive->filename;
        $this->zipArchive->close();
        @unlink($zipFile);
    }

    /**
     * @dataProvider packTestDataProvider
     */
    public function testPack(string $link, string $expectedDir, array $fileNames, array $expectedZippedFiles): void
    {
        $fileSystemMock = $this->initFileSystemMock();

        $fileSystemMock->expects(self::once())
            ->method('directoryExists')
            ->with($expectedDir)
            ->willReturn(true);

        $fileSystemMock->expects(self::any())
            ->method('read')
            ->willReturn('dummyStringContent');

        $fileSystemMock->expects(self::once())
            ->method('listContents')
            ->willReturn(new DirectoryListing($fileNames));


        $sharedStimulusCSSExporterService = $this->getPreparedServiceInstance($fileSystemMock);
        $sharedStimulusCSSExporterService->pack(new core_kernel_classes_Resource("dummyUri"), $link, $this->zipArchive);

        $zippedFiles = $this->getZippedFilesList($this->zipArchive);
        $this->assertEquals($expectedZippedFiles, $zippedFiles);
    }

    public function packTestDataProvider(): array
    {
        $cssZipFolder = SharedStimulusCSSExporter::CSS_ZIP_DIR_NAME . DIRECTORY_SEPARATOR;
        return [
            [
                'test_path/stimulus.xml',
                'test_path/' . StoreService::CSS_DIR_NAME,
                [['basename' => 'file1.css'], ['basename' => 'file2.css']],
                [
                    $cssZipFolder,
                    $cssZipFolder . 'file1.css',
                    $cssZipFolder . 'file2.css'
                ],
            ],
            [
                'test_path/stimulus.xml',
                'test_path/' . StoreService::CSS_DIR_NAME,
                [],
                [],
            ],
            [
                'test_path/stimulusFile',
                'test_path/' . StoreService::CSS_DIR_NAME,
                [['basename' => 'fileX']],
                [
                    $cssZipFolder,
                    $cssZipFolder . 'fileX'
                ],
            ]
        ];
    }

    private function getZippedFilesList(\ZipArchive $archive): array
    {
        $output = [];
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $output[] = $archive->statIndex($i)['name'];
        }

        return $output;
    }

    /**
     * @return FileSystem|MockObject
     */
    private function initFileSystemMock(): FileSystem
    {
        return $this->getMockBuilder(FileSystem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['directoryExists', 'listContents', 'read'])
            ->getMock();
    }

    /**
     * @return SharedStimulusCSSExporter|MockObject
     */
    private function getPreparedServiceInstance(FileSystem $fileSystemMock): SharedStimulusCSSExporter
    {
        $fileSystemServiceProphecy = $this->prophesize(FileSystemService::class);
        $fileSystemServiceProphecy->getFileSystem(Argument::any())->willReturn($fileSystemMock);

        $sharedStimulusResourceSpecificationProphecy = $this->prophesize(SharedStimulusResourceSpecification::class);
        $sharedStimulusResourceSpecificationProphecy->isSatisfiedBy(Argument::any())->willReturn(true);

        $service = new SharedStimulusCSSExporter();
        $service->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    FlySystemManagement::SERVICE_ID => $this->getMockBuilder(FlySystemManagement::class)->getMock(),
                    FileSystemService::SERVICE_ID => $fileSystemServiceProphecy->reveal(),
                    SharedStimulusResourceSpecification::class => $sharedStimulusResourceSpecificationProphecy->reveal(
                    ),
                ]
            )
        );
        return $service;
    }
}
