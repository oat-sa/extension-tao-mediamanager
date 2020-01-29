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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\test\integration\model;

use oat\generis\test\TestCase;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\log\LoggerService;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\import\InvalidSourcePathException;
use oat\tao\model\upload\UploadService;
use oat\taoMediaManager\model\FileImportForm;
use oat\taoMediaManager\model\SharedStimulusPackageImporter;
use Prophecy\Argument;
use Psr\Log\NullLogger;
use qtism\data\storage\xml\XmlDocument;
use oat\generis\test\MockObject;

class SharedStimulusPackageImporterTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $service = null;
    private $tempDirectoryPath;

    public function setUp()
    {
        $this->service = $this->getMockBuilder('oat\taoMediaManager\model\MediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new \ReflectionProperty(\tao_models_classes_Service::class, 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, ['oat\taoMediaManager\model\MediaService' => $this->service]);
    }

    public function tearDown()
    {
        $ref = new \ReflectionProperty(\tao_models_classes_Service::class, 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, []);

        $this->removeTempFileSystem();
    }

    /**
     * @dataProvider sharedStimulusImportProvider
     */
    public function testImport($filename, $expectedSuccess)
    {
        $file = $this->getTempDirectory()->getFile('fixture');

        $fileinfo = [];
        $fileinfo['uploaded_file'] = $filename;
        $fileinfo['name'] = basename($filename);

        if (file_exists($filename)) {
            $file->put(file_get_contents($filename));
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $fileinfo['type'] = finfo_file($info, $filename);
            finfo_close($info);
        } else {
            $file = null;
        }

        $myClass = new \core_kernel_classes_Class('http://fancyDomain.com/tao.rdf#fancyUri');

        $form = new FileImportForm($myClass->getUri());
        $form = $form->getForm();
        $form->setValues(['source' => $fileinfo, 'lang' => 'EN_en']);

        if ($expectedSuccess) {
            $this->service->expects($this->once())
                ->method('createMediaInstance')
                ->willReturn('myGreatLink');
        }

        $report = $this->getPackageImporter($file)->import($myClass, $form);

        $expectedType = $expectedSuccess ? \common_report_Report::TYPE_SUCCESS : \common_report_Report::TYPE_ERROR;
        $this->assertEquals($expectedType, $report->getType());
    }

    /**
     * @dataProvider sharedStimulusImportProvider
     */
    public function testEdit($filename, $expectedSuccess)
    {
        $file = $this->getTempDirectory()->getFile('fixture');

        $fileinfo = [];
        $fileinfo['uploaded_file'] = $filename;
        $fileinfo['name'] = basename($filename);

        if (file_exists($filename)) {
            $file->put(file_get_contents($filename));
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $fileinfo['type'] = finfo_file($info, $filename);
            finfo_close($info);
        } else {
            $file = null;
        }

        $clazz = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');
        $instance = $clazz->createInstance('my Label');

        $form = new FileImportForm($instance->getUri());
        $form = $form->getForm();
        $form->setValues(['source' => $fileinfo, 'lang' => 'EN_en']);

        if ($expectedSuccess) {
            $this->service->expects($this->once())
                ->method('editMediaInstance')
                ->willReturn(true);
        }

        $report = $this->getPackageImporter($file)->edit($instance, $form);

        $expectedType = $expectedSuccess ? \common_report_Report::TYPE_SUCCESS : \common_report_Report::TYPE_ERROR;
        $this->assertEquals($expectedType, $report->getType());
        $instance->delete(true);
    }

    /**
     * @dataProvider sharedStimulusConvertProvider
     *
     * @param string $directory
     * @param string $converted
     *
     * @throws InvalidSourcePathException
     * @throws \common_exception_Error
     * @throws \qtism\data\storage\xml\XmlStorageException
     * @throws \tao_models_classes_FileNotFoundException
     */
    public function testEmbedAssets($directory, $converted)
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->load($directory . '/stimulus.xml');

        $xmlConverted = SharedStimulusPackageImporter::embedAssets($directory . '/stimulus.xml');
        $xmlDocument->load($xmlConverted);
        $strXml = $xmlDocument->saveToString();
        $xmlDocument->load($converted);
        $convertStr = $xmlDocument->saveToString();

        $this->assertEquals($convertStr, $strXml, 'Conversion return a wrong string');
    }

    public function sharedStimulusConvertProvider()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return [
            [$sampleDir . 'stimulusPackage', $sampleDir . 'converted.xml'],
        ];
    }

    /**
     * @expectedException \oat\tao\model\import\InvalidSourcePathException
     * @dataProvider sharedStimulusOutOfThePackageProvider
     *
     * @param string $directory
     *
     * @throws InvalidSourcePathException
     * @throws \common_exception_Error
     * @throws \qtism\data\storage\xml\XmlStorageException
     * @throws \tao_models_classes_FileNotFoundException
     */
    public function testEmbedAssetsExceptions($directory)
    {
        SharedStimulusPackageImporter::embedAssets($directory . '/stimulus.xml');
    }

    /**
     * Providerr that returns packages that are missing files
     * @return string[][]
     */
    public function sharedStimulusOutOfThePackageProvider()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return [
            [$sampleDir . 'missingAssetArchive'],
            [$sampleDir . 'fileOutOfThePackage'],
        ];
    }

    public function sharedStimulusPackage()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return [
            [$sampleDir . 'UnknowFile.zip', new \common_Exception('Unable to open archive ' . $sampleDir . 'UnknowFile.zip')],
            [$sampleDir . 'missingXmlArchive.zip', new \common_Exception('XML not found in the package')],
            [$sampleDir . 'stimulusPackage.zip', null],
            [$sampleDir . 'encodedImage.zip', null],
        ];
    }

    public function sharedStimulusImportProvider()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return [
            [$sampleDir . 'encodedImage.zip', true],
            [$sampleDir . 'UnknowFile.zip', false],
            [$sampleDir . 'missingXmlArchive.zip', false],
            [$sampleDir . 'stimulusPackage.zip', true],
            [$sampleDir . 'objectOutOfThePackage.zip', false],
            [$sampleDir . 'fileOutOfThePackage.zip', false],
        ];
    }

    private function getPackageImporter()
    {
        $uploadServiceMock = $this->getMockBuilder(UploadService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uploadServiceMock->expects($this->any())
            ->method('remove')
            ->willReturn(true);

        $sm = $this->prophesize(ServiceManager::class);
        $sm->get(Argument::is(UploadService::SERVICE_ID))->willReturn($uploadServiceMock);
        $sm->get(Argument::is(LoggerService::SERVICE_ID))->willReturn(new NullLogger());

        $importer = new SharedStimulusPackageImporter();
        $importer->setServiceLocator($sm->reveal());

        return $importer;
    }

    protected function getTempDirectory()
    {
        $this->tempDirectoryPath = '/tmp/testing-' . uniqid('test');
        $directoryName = 'test-dir-' . uniqid();

        $fileSystemService = new FileSystemService([
            FileSystemService::OPTION_FILE_PATH => '/tmp/testing',
            FileSystemService::OPTION_ADAPTERS => [
                $directoryName => [
                    'class' => FileSystemService::FLYSYSTEM_LOCAL_ADAPTER,
                    'options' => ['root' => $this->tempDirectoryPath]
                ]
            ],
        ]);

        $fileSystemService->setServiceLocator($this->getServiceLocatorMock([
            FileSystemService::SERVICE_ID => $fileSystemService
        ]));

        return $fileSystemService->getDirectory($directoryName);
    }

    protected function removeTempFileSystem()
    {
        if ($this->getTempDirectory()->exists()) {
            $this->rrmdir($this->tempDirectoryPath);
        }
    }

    /**
     * Remove a local directory recursively
     *
     * @param $dir
     */
    protected function rrmdir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}
