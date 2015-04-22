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

use oat\taoMediaManager\model\FileImportForm;
use oat\taoMediaManager\model\SharedStimulusPackageImporter;
use qtism\data\storage\xml\XmlDocument;
include_once dirname(__FILE__) . '/../../includes/raw_start.php';


class SharedStimulusPackageImporterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $packageImporter SharedStimulusPackageImporter
     */
    private $packageImporter = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $service = null;

    public function setUp()
    {
        $this->packageImporter = new SharedStimulusPackageImporter();

        $this->service = $this->getMockBuilder('oat\taoMediaManager\model\MediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array('oat\taoMediaManager\model\MediaService' => $this->service));
    }

    public function tearDown(){
        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array());
    }

    /**
     * @dataProvider sharedStimulusImportProvider
     */
    public function testImport($filename, $expectedReport, $called){
        if(file_exists($filename)){
            $tmpDir = \tao_helpers_File::createTempDir();
            copy($filename, $tmpDir.basename($filename));
            $filename = $tmpDir.basename($filename);
        }

        $myClass = new \core_kernel_classes_Class('http://fancyDomain.com/tao.rdf#fancyUri');
        $file['uploaded_file'] = $filename;
        $file['name'] = basename($filename);

        $form = new FileImportForm($myClass->getUri());
        $form = $form->getForm();
        $form->setValues(array('source' => $file, 'lang' => 'EN_en'));

        if($called){
            $this->service->expects($this->once())
                ->method('createMediaInstance')
                ->willReturn('myGreatLink');
        }

        $report = $this->packageImporter->import($myClass,$form);

        $this->assertEquals($expectedReport->getType(),$report->getType(), __('Report should be success'));
        $this->assertEquals($expectedReport->getMessage(),$report->getMessage(), __('Report message is wrong'));

    }

    /**
     * @dataProvider sharedStimulusEditProvider
     */
    public function testEdit($filename, $expectedReport, $called){

        if(file_exists($filename)){
            $tmpDir = \tao_helpers_File::createTempDir();
            copy($filename, $tmpDir.basename($filename));
            $filename = $tmpDir.basename($filename);
        }

        $clazz = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');
        $instance = $clazz->createInstance('my Label');
        $file['uploaded_file'] = $filename;
        $file['name'] = basename($filename);

        $form = new FileImportForm($instance->getUri());
        $form = $form->getForm();
        $form->setValues(array('source' => $file, 'lang' => 'EN_en'));

        if($called){
            $this->service->expects($this->once())
                ->method('editMediaInstance')
                ->willReturn(true);
        }

        $report = $this->packageImporter->edit($instance,$form);

        $this->assertEquals($expectedReport->getMessage(),$report->getMessage(), __('Report message is wrong'));
        $this->assertEquals($expectedReport->getType(),$report->getType(), __('Report should be success'));
        $instance->delete(true);
    }

    public function testValidateAndStoreSharedStimulus(){

        $class = new \core_kernel_classes_Class('myGreatClassUri');
        $tmpDir = \tao_helpers_File::createTempDir();
        $this->service->expects($this->exactly(2))
            ->method('createMediaInstance')
            ->with($tmpDir.'/sharedStimulus.xml', $class->getUri(), 'en_EN')
            ->willReturnOnConsecutiveCalls(array(true,false));

        $report = $this->packageImporter->setXmlFile(dirname(__DIR__) . '/sample/sharedStimulus/stimulusPackage/stimulus.xml')
            ->setDirectory(dirname(__DIR__) . '/sample/sharedStimulus/stimulusPackage/')
            ->validateAndStoreSharedStimulus($class, 'en_EN', $tmpDir, $tmpDir);

        $expected = \common_report_Report::createSuccess(__('Shared Stimulus imported successfully'));
        $this->assertEquals($expected->getType(), $report->getType(), __('Import should succeed and create a success report'));
        $this->assertEquals($expected->getMessage(), $report->getMessage(), __('Report message is not the right one'));

        $report = $this->packageImporter->setXmlFile(dirname(__DIR__) . '/sample/sharedStimulus/stimulusPackage/stimulus.xml')
            ->setDirectory(dirname(__DIR__) . '/sample/sharedStimulus/stimulusPackage/')
            ->validateAndStoreSharedStimulus($class, 'en_EN', $tmpDir, $tmpDir);

        $expected = \common_report_Report::createFailure(__('Fail to import Shared Stimulus'));
        $this->assertEquals($expected->getType(), $report->getType(), __('Import should fail and create a fail report'));
        $this->assertEquals($expected->getMessage(), $report->getMessage(), __('Report message is not the right one'));

    }

    public function testValidateAndEditSharedStimulus(){
        $clazz = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');
        $instance = $clazz->createInstance('my Label');
        $tmpDir = \tao_helpers_File::createTempDir();
        $this->service->expects($this->exactly(2))
            ->method('editMediaInstance')
            ->with($tmpDir.'/sharedStimulus.xml', $instance->getUri(), 'en_EN')
            ->willReturnOnConsecutiveCalls(array(true,false));

        $report = $this->packageImporter->setXmlFile(dirname(__DIR__) . '/sample/sharedStimulus/editStimulus/stimulus.xml')
            ->setDirectory(dirname(__DIR__) . '/sample/sharedStimulus/editStimulus/')
            ->validateAndEditSharedStimulus($instance, 'en_EN', $tmpDir);

        $expected = \common_report_Report::createSuccess(__('Shared Stimulus edited successfully'));
        $this->assertEquals($expected->getType(), $report->getType(), __('Edit should succeed and create a success report'));
        $this->assertEquals($expected->getMessage(), $report->getMessage(), __('Report message is not the right one'));

        $report = $this->packageImporter->setXmlFile(dirname(__DIR__) . '/sample/sharedStimulus/editStimulus/stimulus.xml')
            ->validateAndEditSharedStimulus($instance, 'en_EN', $tmpDir);

        $expected = \common_report_Report::createFailure(__('Fail to edit Shared Stimulus'));
        $this->assertEquals($expected->getType(), $report->getType(), __('Edit should fail and create a fail report'));
        $this->assertEquals($expected->getMessage(), $report->getMessage(), __('Report message is not the right one'));

        $instance->delete(true);

    }

    public function testValidateAndEditSharedStimulusFail(){

        $instance = new \core_kernel_classes_Resource('fakeInstance');
        $expected = \common_report_Report::createFailure('The instance fakeInstance is not a Media instance');

        $report = $this->packageImporter->validateAndEditSharedStimulus($instance, 'en_EN');
        $this->assertEquals($expected->getType(), $report->getType(), __('Edit should fail and create a fail report'));
        $this->assertEquals($expected->getMessage(), $report->getMessage(), __('Report message is not the right one'));
    }

    /**
     * @dataProvider sharedStimulusConvertProvider
     */
    public function testConvertEmbeddedFiles($directory, $exception, $converted){

        $this->packageImporter->setDirectory($directory);
        $xmlDocument = new XmlDocument();
        $xmlDocument->load($this->packageImporter->getDirectory().'/stimulus.xml');

        try{
            $xmlConverted = $this->packageImporter->convertEmbeddedFiles($xmlDocument);
            $strXml = $xmlConverted->saveToString();
            $xmlDocument->load($converted);
            $convertStr = $xmlDocument->saveToString();

            $this->assertEquals($convertStr, $strXml, __('Conversion return a wrong string'));
        }
        catch(\tao_models_classes_FileNotFoundException $e){
            $this->assertNotNull($exception, __('It should not throw an exception'));
            if(!is_null($e)){
                $this->assertInstanceOf(get_class($exception), $e, __('The exception class is wrong'));
                if($exception->getMessage() !== ''){
                    $this->assertEquals($exception->getMessage(), $e->getMessage(), __('The exception message is wrong'));
                }
            }
        }


    }


    public function sharedStimulusConvertProvider(){
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return array(
            array($sampleDir.'stimulusPackage', null, $sampleDir.'converted.xml'),
            array($sampleDir.'missingAssetArchive', new \tao_models_classes_FileNotFoundException('images/image1.jpg'), null),
        );
    }

    public function sharedStimulusImportProvider(){
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return array(
            array($sampleDir.'stimulus/../Package.zip', \common_report_Report::createFailure(__('Filename is unsafe')), false),
            array($sampleDir.'UnknowFile.zip', \common_report_Report::createFailure(__('Unable to move uploaded file')), false),
            array($sampleDir.'missingXmlArchive.zip', \common_report_Report::createFailure('Unable to find an xml file in you package'), false),
            array($sampleDir.'stimulusPackage.zip', \common_report_Report::createSuccess(__('Shared Stimulus imported successfully')), true),
        );
    }

    public function sharedStimulusEditProvider(){
        $sampleDir = dirname(__DIR__) . '/sample/sharedStimulus/';
        return array(
            array($sampleDir.'stimulus/../Package.zip', \common_report_Report::createFailure(__('Filename is unsafe')), false),
            array($sampleDir.'UnknowFile.zip', \common_report_Report::createFailure(__('Unable to move uploaded file')), false),
            array($sampleDir.'missingXmlArchive.zip', \common_report_Report::createFailure('Unable to find an xml file in you package'), false),
            array($sampleDir.'stimulusPackage.zip', \common_report_Report::createSuccess(__('Shared Stimulus edited successfully')), true),
        );
    }

}
 