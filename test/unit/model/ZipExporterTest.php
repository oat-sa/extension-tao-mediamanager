<?php

namespace oat\taoMediaManager\test\unit\model;

use common_Exception;
use core_kernel_classes_Resource;
use oat\oatbox\service\ServiceManager;
use oat\taoMediaManager\model\export\service\MediaReferencesNotFoundException;
use oat\taoMediaManager\model\export\service\MediaResourcePreparerInterface;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\ZipExporter;
use oat\taoMediaManager\model\ZipExporterFileErrorList;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ZipExporterTest extends TestCase
{
    private const LINK = 'link';

    private const FILENAME = 'test';

    private const EXPORT_DIR = '/tmp/tao_export/';

    private const RESOURCES_DIR = __DIR__ . '/../../resources/';

    private const TEST_ZIP = 'test.zip';

    private const EMPTY_ZIP = 'empty.zip';

    private StreamInterface $streamMock;

    private core_kernel_classes_Resource $resourceMock;

    private FileManagement $fileManagementMock;

    private MediaResourcePreparerInterface $mediaResourcePreparerMock;

    private ServiceManager $serviceManagerMock;

    private ZipExporter $sut;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        if (!is_dir(self::EXPORT_DIR)) {
            mkdir(self::EXPORT_DIR);
        }

        $this->streamMock = $this->createMock(StreamInterface::class);
        $this->resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $this->fileManagementMock = $this->createMock(FileManagement::class);
        $this->mediaResourcePreparerMock = $this->createMock(MediaResourcePreparerInterface::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);

        $this->sut = $this
            ->getMockBuilder(ZipExporterTester::class)
            ->onlyMethods(['getServiceManager'])
            ->getMock();
    }

    /**
     * @throws common_Exception
     */
    public function testCreateZipFile()
    {
        $exportClasses = [
            'foo'
        ];

        $exportFiles = [
            'foo' => [
                $this->resourceMock
            ]
        ];

        copy(self::RESOURCES_DIR . self::TEST_ZIP, self::EXPORT_DIR . self::TEST_ZIP);

        $this->sut->createZipFile(self::FILENAME, $exportClasses, $exportFiles);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws common_Exception
     */
    public function testCreateZipFileThrowsMediaReferencesNotFoundException(): void
    {
        $exportClasses = [
            'foo'
        ];

        $exportFiles = [
            'foo' => [
                $this->resourceMock
            ]
        ];

        $missingMediaAssets = [
            'Image: foo.jpg FilePath: foo.jpg'
        ];

        $this
            ->sut
            ->expects(self::exactly(2))
            ->method('getServiceManager')
            ->willReturn($this->serviceManagerMock);

        $this
            ->fileManagementMock
            ->expects(self::once())
            ->method('getFileStream')
            ->willReturn($this->streamMock);

        $this
            ->mediaResourcePreparerMock
            ->expects(self::once())
            ->method('prepare')
            ->willThrowException(new MediaReferencesNotFoundException($missingMediaAssets));

        $this
            ->serviceManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($this->fileManagementMock, $this->mediaResourcePreparerMock);

        $this->expectException(ZipExporterFileErrorList::class);
        $this->expectExceptionMessage(
            'Errors in zip file: <br>' .
            'Error in Asset class "foo": Media references to Image: foo.jpg FilePath: foo.jpg could not be found.'
        );

        copy(self::RESOURCES_DIR . self::EMPTY_ZIP, self::EXPORT_DIR . self::TEST_ZIP);

        $this->sut->createZipFile(self::FILENAME, $exportClasses, $exportFiles);
    }

    public function tearDown(): void
    {
        if (is_file(self::EXPORT_DIR . self::TEST_ZIP)) {
            unlink(self::EXPORT_DIR . self::TEST_ZIP);
        }

        if (is_file(self::EXPORT_DIR . self::EMPTY_ZIP)) {
            unlink(self::EXPORT_DIR . self::EMPTY_ZIP);
        }

        if (is_dir(self::EXPORT_DIR)) {
            rmdir(self::EXPORT_DIR);
        }
    }
}
