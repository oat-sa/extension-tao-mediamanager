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

use oat\oatbox\service\ServiceManager;
use oat\tao\model\upload\UploadService;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\SharedStimulusImporter;
use Prophecy\Argument;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;
use Zend\ServiceManager\ServiceLocatorInterface;

include_once dirname(__FILE__) . '/../../../../tao/includes/raw_start.php';

class SharedStimulusImporterTest extends TaoPhpUnitTestRunner
{
    /**
     * @var MediaService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $service;

    public function setUp()
    {
        $this->service = $this->getMockBuilder('oat\taoMediaManager\model\MediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array('oat\taoMediaManager\model\MediaService' => $this->service));
    }

    public function tearDown()
    {
        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array());
        $tmpDir = dirname(__DIR__) . '/sample/fs/';
        $iterator = new \DirectoryIterator ($tmpDir);
        foreach ($iterator as $info) {
            if ($info->isFile() && $info->getBasename() !== '.gitignore') {
                unlink($info->getPath() . '/' . $info->getFilename());
            }
        }

        $this->removeTempFileSystem();
    }

    public function ttestGetLabel()
    {
        $sharedImporter = new SharedStimulusImporter();
        $this->assertEquals('Shared Stimulus', $sharedImporter->getLabel(), __('The label is wrong'));
    }

    /**
     * @dataProvider sharedStimulusFilenameProvider
     */
    public function ttestIsValidSharedStimulus($filename, $response, $exception)
    {
        try {
            $xmlDocumentValid = SharedStimulusImporter::isValidSharedStimulus($filename);
            $this->assertTrue($response, __('It should not be valid'));
            $xmlDocument = new XmlDocument();
            $xmlDocument->load($filename);
            $this->assertEquals($xmlDocument->getDomDocument()->C14N(), $xmlDocumentValid->getDomDocument()->C14N(), __('The loaded cml is wrong'));
        } catch (\Exception $e) {
            $this->assertFalse($response, __('It should not throw an exception'));
            if (!is_null($e)) {
                $this->assertInstanceOf(get_class($exception), $e, __('The exception class is wrong'));
                if ($exception->getMessage() !== '') {
                    $this->assertEquals($exception->getMessage(), $e->getMessage(), __('The exception message is wrong'));
                }
            }
        }
    }

    public function testImportXml()
    {
        $filename = dirname(__DIR__) . '/sample/sharedStimulus/sharedStimulus.xml';
        $file = $this->getTempDirectory()->getFile('fixture');
        $file->put(file_get_contents($filename));

        $sharedImporter = $this->getSharedStimulusImporter($file);

        $info = finfo_open(FILEINFO_MIME_TYPE);
        $fileinfo = [];
        $fileinfo['type'] = finfo_file($info, $filename);
        finfo_close($info);
        $fileinfo['uploaded_file'] = $filename;
        $fileinfo['name'] = basename($filename);

        $form = $sharedImporter->getForm();
        $form->setValues(array('source' => $fileinfo, 'lang' => 'EN_en'));

        $myClass = new \core_kernel_classes_Class('http://fancyDomain.com/tao.rdf#fancyUri');
        $this->service->expects($this->once())
            ->method('createMediaInstance')
            ->with($file, $myClass->getUri(), 'EN_en', basename($filename))
            ->willReturn('myGreatLink');

        $report = $sharedImporter->import($myClass, $form);

        $this->assertEquals(\common_report_Report::TYPE_SUCCESS, $report->getType(), __('Report should be success'));
        $this->assertEquals(__('Shared Stimulus imported successfully'), $report->getMessage(), __('Report message is wrong'));
    }

    public function testEditXml()
    {
        $filename = dirname(__DIR__) . '/sample/sharedStimulus/sharedStimulus.xml';
        $instance = new \core_kernel_classes_Resource('http://fancyDomain.com/tao.rdf#fancyInstanceUri');

        $file = $this->getTempDirectory()->getFile('fixture');
        $file->put(file_get_contents($filename));

        $sharedImporter = $this->getSharedStimulusImporter($file, $instance->getUri());

        $myClass = new \core_kernel_classes_Class('http://fancyDomain.com/tao.rdf#fancyUri');
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $fileinfo['type'] = finfo_file($info, $filename);
        finfo_close($info);
        $fileinfo['uploaded_file'] = $filename;
        $fileinfo['name'] = $filename;

        $form = $sharedImporter->getForm();
        $form->setValues(array('source' => $fileinfo, 'lang' => 'EN_en'));

        $filenameXhtml = dirname($filename) . DIRECTORY_SEPARATOR . basename($filename, 'xml') .'xhtml';
        $this->service->expects($this->once())
            ->method('editMediaInstance')
            ->with($filenameXhtml, $instance->getUri(), 'EN_en')
            ->willReturn(true);

        $report = $sharedImporter->import($myClass, $form);

        $this->assertEquals(__('Shared Stimulus edited successfully'), $report->getMessage(), __('Report message is wrong'));
        $this->assertEquals(\common_report_Report::TYPE_SUCCESS, $report->getType(), __('Report should be success'));
        $this->assertTrue($file->exists());

        $xhtmlFile = dirname($filename) . DIRECTORY_SEPARATOR . basename($filename, 'xml') . 'xhtml';
        if (file_exists($xhtmlFile)) {
            unlink($xhtmlFile);
        }
    }

    public function testImportPackage()
    {
        $packageImporter = $this->getMockBuilder('oat\taoMediaManager\model\SharedStimulusPackageImporter')
            ->disableOriginalConstructor()
            ->getMock();

        $sharedImporter = new SharedStimulusImporter();

        $serviceMangerMock = $this->getMock(ServiceLocatorInterface::class);
        $uploadServiceMock = $this->getMock(UploadService::class);

        $serviceMangerMock->expects($this->atLeastOnce())
            ->method('get')
            ->with(UploadService::SERVICE_ID)
            ->willReturn($uploadServiceMock);

        $sharedImporter->setServiceLocator($serviceMangerMock);

        $filename = dirname(__DIR__) . '/sample/sharedStimulus/stimulusPackage.zip';
        $myClass = new \core_kernel_classes_Class('http://fancyDomain.com/tao.rdf#fancyUri');
        $file['type'] = 'application/zip';
        $file['uploaded_file'] = $filename;

        $form = $sharedImporter->getForm();
        $form->setValues(array('source' => $file, 'lang' => 'EN_en'));

        $returnReport = \common_report_Report::createSuccess('Success');
        $packageImporter->expects($this->once())
            ->method('import')
            ->with($myClass, $form)
            ->willReturn($returnReport);

        $sharedImporter->setZipImporter($packageImporter);
        $report = $sharedImporter->import($myClass, $form);

        $this->assertEquals($returnReport->getMessage(), $report->getMessage(), __('Report message is wrong'));
        $this->assertEquals($returnReport->getType(), $report->getType(), __('Report should be success'));
    }

    public function testEditPackage()
    {
        $packageImporter = $this->getMockBuilder('oat\taoMediaManager\model\SharedStimulusPackageImporter')
            ->getMock();

        $instance = new \core_kernel_classes_Resource('http://fancyDomain.com/tao.rdf#fancyInstanceUri');
        $sharedImporter = new SharedStimulusImporter($instance->getUri());

        $serviceMangerMock = $this->getMock(ServiceLocatorInterface::class);
        $uploadServiceMock = $this->getMock(UploadService::class);

        $serviceMangerMock->expects($this->atLeastOnce())
            ->method('get')
            ->with(UploadService::SERVICE_ID)
            ->willReturn($uploadServiceMock);

        $sharedImporter->setServiceLocator($serviceMangerMock);

        $filename = dirname(__DIR__) . '/sample/sharedStimulus/stimulusPackage.zip';
        $myClass = new \core_kernel_classes_Class('http://fancyDomain.com/tao.rdf#fancyUri');
        $file['type'] = 'application/zip';
        $file['uploaded_file'] = $filename;

        $form = $sharedImporter->getForm();
        $form->setValues(array('source' => $file, 'lang' => 'EN_en'));

        $returnReport = \common_report_Report::createSuccess('Success');
        $packageImporter->expects($this->once())
            ->method('edit')
            ->with($instance, $form)
            ->willReturn($returnReport);

        $sharedImporter->setZipImporter($packageImporter);
        $report = $sharedImporter->import($myClass, $form);
        $this->assertEquals($returnReport->getMessage(), $report->getMessage(), __('Report message is wrong'));
        $this->assertEquals($returnReport->getType(), $report->getType(), __('Report should be success'));
    }

    public function sharedStimulusFilenameProvider()
    {
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return array(
            array($sampleDir . 'sharedStimulus.xml', true, null),
            /** TODO :  this sample should come back once the qtsim validate apip file
             * and the SharedStimulusImporter l54 $xmlDocument->load($filename, false); should validate files*/
//            array($sampleDir . 'wrongParsing.xml', false, new XmlStorageException('')),
            array($sampleDir . 'feedback.xml', false, new XmlStorageException("The shared stimulus contains feedback QTI components.")),
            array($sampleDir . 'template.xml', false, new XmlStorageException("The shared stimulus contains template QTI components.")),
            array($sampleDir . 'interactions.xml', false, new XmlStorageException("The shared stimulus contains interactions QTI components."))
        );
    }

    private function getSharedStimulusImporter($file, $uri = null)
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

        $importer = $this->getMockBuilder(SharedStimulusImporter::class)->setMethods(['getServiceLocator']);
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
 