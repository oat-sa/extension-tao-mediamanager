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

namespace oat\taoMediaManager\test\model;

use oat\oatbox\service\ServiceManager;
use oat\tao\model\upload\UploadService;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\FileImportForm;
use oat\taoMediaManager\model\InvalidSourcePathException;
use oat\taoMediaManager\model\SharedStimulusPackageImporter;
use Prophecy\Argument;
use qtism\data\storage\xml\XmlDocument;

class SharedStimulusPackageImporterTest extends TaoPhpUnitTestRunner
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $service = null;

    public function setUp()
    {
        $this->service = $this->getMockBuilder('oat\taoMediaManager\model\MediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, ['oat\taoMediaManager\model\MediaService' => $this->service]);
    }

    public function tearDown()
    {
        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array());

        $this->removeTempFileSystem();
    }

    /**
     * @dataProvider sharedStimulusImportProvider
     */
    public function testImport($filename, $expectedReport, $called)
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
        $form->setValues(array('source' => $fileinfo, 'lang' => 'EN_en'));

        if ($called) {
            $this->service->expects($this->once())
                ->method('createMediaInstance')
                ->willReturn('myGreatLink');
        }

        $report = $this->getPackageImporter($file)->import($myClass, $form);

        /** @var \common_report_Report $expectedReport */
        $expectedReport->setMessage(preg_replace('/%s/', 'imported', $expectedReport->getMessage()));
        $this->assertEquals($expectedReport->getType(), $report->getType(), __('Report should be success'));
        $this->assertEquals($expectedReport->getMessage(), $report->getMessage(), __('Report message is wrong'));

    }

    /**
     * @dataProvider sharedStimulusImportProvider
     */
    public function testEdit($filename, $expectedReport, $called)
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
        $form->setValues(array('source' => $fileinfo, 'lang' => 'EN_en'));

        if ($called) {
            $this->service->expects($this->once())
                ->method('editMediaInstance')
                ->willReturn(true);
        }

        $report = $this->getPackageImporter($file)->edit($instance, $form);

        /** @var \common_report_Report $expectedReport */
        $expectedReport->setMessage(preg_replace('/%s/', 'edited', $expectedReport->getMessage()));
        $this->assertEquals($expectedReport->getMessage(), $report->getMessage(), __('Report message is wrong'));
        $this->assertEquals($expectedReport->getType(), $report->getType(), __('Report should be success'));
        $instance->delete(true);
    }

    /**
     * @dataProvider sharedStimulusPackageProvider
     */
    public function testGetSharedStimulusFile($filename, $exception)
    {
        $file = $this->getTempDirectory()->getFile($filename);
        if (file_exists($filename)) {
            $file->put(file_get_contents($filename));
        }
        try {
            $method = new \ReflectionMethod('oat\taoMediaManager\model\SharedStimulusPackageImporter', 'getSharedStimulusFile');
            $method->setAccessible(true);
            $packageImporter = new SharedStimulusPackageImporter();;
            $xmlFile = $method->invokeArgs($packageImporter, array($file));
            $xmlFile = str_replace('\\', '/', $xmlFile);
            $this->assertContains('stimulus.xml', $xmlFile);
        } catch (\Exception $e) {
            $this->assertNotNull($exception, __('It should not throw an exception'));
            if (!is_null($e)) {
                $this->assertInstanceOf(get_class($exception), $e, __('The exception class is wrong'));
                if ($exception->getMessage() !== '') {
                    $this->assertEquals($exception->getMessage(), $e->getMessage(), __('The exception message is wrong'));
                }
            }
        }
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
        return array(
            array($sampleDir . 'stimulusPackage', $sampleDir . 'converted.xml'),
        );
    }

    /**
     * @expectedException \oat\taoMediaManager\model\InvalidSourcePathException
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
        return array(
            [$sampleDir . 'missingAssetArchive'],
            [$sampleDir . 'fileOutOfThePackage'],
        );
    }

    /**
     * Provider that returns packages with the corresponding exception
     */
    public function sharedStimulusPackageProvider()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return array(
            array($sampleDir . 'UnknowFile.zip', new \common_Exception('Unable to open archive '.$sampleDir . 'UnknowFile.zip')),
            array($sampleDir . 'missingXmlArchive.zip', new \common_Exception('XML not found in the package')),
            array($sampleDir . 'stimulusPackage.zip', null),
        );
    }

    public function sharedStimulusImportProvider()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return array(
            array($sampleDir . 'UnknowFile.zip', \common_report_Report::createFailure(__('Unable to get uploaded file')), false),
            array($sampleDir . 'missingXmlArchive.zip', \common_report_Report::createFailure('XML not found in the package'), false),
            array($sampleDir . 'stimulusPackage.zip', \common_report_Report::createSuccess(__('Shared Stimulus %s successfully')), true),
        );
    }

    private function getPackageImporter($file, $uri = null)
    {
        $uploadServiceMock = $this->getMockBuilder(UploadService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uploadServiceMock->expects($this->once())
            ->method('getUploadedFlyFile')
            ->willReturn($file);
        $uploadServiceMock->expects($this->any())
            ->method('remove')
            ->willReturn(true);

        $sm = $this->prophesize(ServiceManager::class);
        $sm->get(Argument::is(UploadService::SERVICE_ID))->willReturn($uploadServiceMock);

        $importer = $this->getMockBuilder(SharedStimulusPackageImporter::class)->setMethods(['getServiceLocator']);
        if (!is_null($uri)) {
            $importer->setConstructorArgs([$uri]);
        }
        $importer = $importer->getMock();
        $importer->expects($this->once())
            ->method('getServiceLocator')
            ->willReturn($sm->reveal());

        return $importer;
    }
}
 