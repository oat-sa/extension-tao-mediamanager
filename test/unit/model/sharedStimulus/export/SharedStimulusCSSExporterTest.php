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
use Generator;
use League\Flysystem\DirectoryListing;
use PHPUnit\Framework\MockObject\MockObject;
use oat\generis\test\ServiceManagerMockTrait;
use PHPUnit\Framework\TestCase;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoMediaManager\model\export\service\SharedStimulusCSSExporter;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use ZipArchive;

class SharedStimulusCSSExporterTest extends TestCase
{
    use ServiceManagerMockTrait;

    private ZipArchive $zipArchive;

    public function setUp(): void
    {
        $this->zipArchive = new ZipArchive();
        $zipFile = '/tmp/' . uniqid();
        $this->zipArchive->open($zipFile, ZipArchive::CREATE);
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
    public function testPack(string $link, string $expectedDir, iterable $fileNames, array $expectedZippedFiles): void
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
                [['path' => 'file1.css', 'type' => 'file'], ['path' => 'file2.css', 'type' => 'file']],
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
                [['path' => 'fileX', 'type' => 'file']],
                [
                    $cssZipFolder,
                    $cssZipFolder . 'fileX'
                ],
            ],
            [
                'test_path/stimulusFile',
                'test_path/' . StoreService::CSS_DIR_NAME,
                $this->createGenerator([['path' => 'fileX', 'type' => 'file']]),
                [
                    $cssZipFolder,
                    $cssZipFolder . 'fileX'
                ],
            ]
        ];
    }

    private function getZippedFilesList(ZipArchive $archive): array
    {
        $output = [];
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $output[] = $archive->statIndex($i)['name'];
        }

        return $output;
    }

    private function initFileSystemMock(): FileSystem|MockObject
    {
        return $this->getMockBuilder(FileSystem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['directoryExists', 'listContents', 'read'])
            ->getMock();
    }

    private function getPreparedServiceInstance(FileSystem $fileSystemMock): SharedStimulusCSSExporter|MockObject
    {
        $fileSystemService = $this->createMock(FileSystemService::class);
        $fileSystemService->method('getFileSystem')->with($this->anything())->willReturn($fileSystemMock);

        $sharedStimulusResourceSpecification = $this->createMock(SharedStimulusResourceSpecification::class);
        $sharedStimulusResourceSpecification->method('isSatisfiedBy')->with($this->anything())->willReturn(true);

        $fileSourceUnserializerMock = $this->createMock(FileSourceUnserializer::class);
        $fileSourceUnserializerMock->method('unserialize')->willReturnCallback(function ($link) {
            return $link;
        });

        $service = new SharedStimulusCSSExporter();
        $service->setServiceLocator($this->getServiceManagerMock([
            FlySystemManagement::SERVICE_ID => $this->getMockBuilder(FlySystemManagement::class)->getMock(),
            FileSystemService::SERVICE_ID => $fileSystemService,
            SharedStimulusResourceSpecification::class => $sharedStimulusResourceSpecification,
            FileSourceUnserializer::class => $fileSourceUnserializerMock,
        ]));

        return $service;
    }

    private function createGenerator(array $values): Generator
    {
        foreach ($values as $value) {
            yield $value;
        }
    }
}
