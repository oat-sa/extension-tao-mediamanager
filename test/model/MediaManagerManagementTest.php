<?php

namespace oat\taoMediaManager\test\model;



use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\MediaManagerManagement;

include_once dirname(__FILE__) . '/../../includes/raw_start.php';

class MediaManagerManagementTest extends TaoPhpUnitTestRunner {

    /**
     * @var MediaManagerManagement
     */
    private $mediaManagerManagement = null;

    private $path = null;

    public function setup(){
        TaoPhpUnitTestRunner::initTest();
        $this->path = 'http://myFancyDomaine.com/myGreatCLassUriToUploadTo';
        $this->mediaManagerManagement = new MediaManagerManagement(array('lang' => 'EN_en', 'rootClass' => $this->path));
    }


    public function testUpload(){

        $filePath = dirname(__DIR__).'/sample/Italy.png';


        $success = $this->mediaManagerManagement->upload($filePath, 'Italy.png', $this->path);

        // has no error
        $this->assertArrayNotHasKey('error', $success, 'upload doesn\'t succeed');

        // should create an instance of the file under the root class
        $rootClass = new \core_kernel_classes_Class($this->path);
        $instances = $rootClass->getInstances();
        $instance = array_pop($instances);

        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'It should create an instance under the class in parameter');
        $this->assertEquals('Italy.png', $instance->getLabel(), 'The instance label is wrong');
        $this->assertEquals('EN_en', $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LANGUAGE)), 'The instance language is wrong');

    }

    public function testUploadFail(){

        $filePath = dirname(__DIR__).'/sample/Unknown.png';

        $success = $this->mediaManagerManagement->upload($filePath, 'Unknown.png', $this->path);

        // has no error
        $this->assertArrayHasKey('error', $success, 'upload doesn\'t succeed');

        // should create an instance of the file under the root class
        $rootClass = new \core_kernel_classes_Class($this->path);
        $instances = $rootClass->getInstances();
        $instance = array_pop($instances);

        $this->assertNotEquals('Unknown.png', $instance->getLabel(), 'The instance should not be created');

    }

    public function testDelete(){

        $link = '/Users/Antoine/workspace/package-tao/taoMediaManager/media/italy.png';

        $rootClass = new \core_kernel_classes_Class($this->path);
        $instances = $rootClass->searchInstances(array(MEDIA_LINK => $link), array('recursive' => true));
        $instance = array_pop($instances);
        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'This class should exists');

        $success = $this->mediaManagerManagement->delete($link);

        // should return true
        $this->assertTrue($success, 'The file is not deleted');

        // should remove the file
        $this->assertFileNotExists($link, 'The file still exists');

        // should remove the instance
        $removedInstance = new \core_kernel_classes_Class($instance->getUri());
        $this->assertEquals('',$instance->getLabel(), 'The instance still exists');
        $this->assertEquals('',$removedInstance->getLabel(), 'The instance still exists');

    }

}
 