<?php

namespace oat\taoMediaManager\test\model;



use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\MediaService;

include_once dirname(__FILE__) . '/../../includes/raw_start.php';

class MediaServiceTest extends TaoPhpUnitTestRunner {

    /**
     * @var MediaService
     */
    private $mediaService = null;

    public function setup(){
        TaoPhpUnitTestRunner::initTest();
        $this->mediaService = MediaService::singleton();
    }

    public function testGetRootClass(){
        $rootClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');

        $this->assertEquals($rootClass, $this->mediaService->getRootClass(), 'The root class of the service is not correct');
    }

    public function testCreateMediaInstance(){


        $fileTmp = dirname(__DIR__).'/sample/Brazil.png';
        $lang = 'EN-en';
        $classUri = 'http://myFancyDomain.com/myGreatUri';

        $link = $this->mediaService->createMediaInstance($fileTmp,$classUri,$lang);

        $root = new \core_kernel_classes_Class($classUri);
        $instances = $root->getInstances();
        /** @var \core_kernel_classes_Resource $instance */
        $instance = array_pop($instances);

        $thing = $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK));
        $linkResult = $thing instanceof \core_kernel_classes_Resource ? $thing->getUri() : (string)$thing;
        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'It should create an instance under the class in parameter');
        $this->assertEquals('Brazil.png', $instance->getLabel(), 'The instance label is wrong');
        $this->assertInternalType('string', $link, 'The method return should be a string');
        $this->assertEquals($link, $linkResult, 'The instance link is wrong');
        $this->assertEquals($lang, $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LANGUAGE)), 'The instance language is wrong');

        // remove what has been done
        foreach($instances as $inst){
            $inst->delete();
        }

    }

    public function testEditMediaInstance(){

        $fileTmp = dirname(__DIR__).'/sample/Italy.png';
        $lang = 'EN-en';
        $instanceUri = 'http://myFancyDomain.com/myGreatInstanceUri';

        $this->mediaService->editMediaInstance($fileTmp,$instanceUri,$lang);

        $instance = new \core_kernel_classes_Class($instanceUri);
        $this->assertEquals($lang, $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LANGUAGE)), 'The instance language is wrong');

        // remove what has been done
        $inst = new \core_kernel_classes_Resource($instanceUri);
        $inst->delete();
    }
}
 