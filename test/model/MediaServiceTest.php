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

        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'It should create an instance under the class in parameter');
        $this->assertEquals('Brazil.png', $instance->getLabel(), 'The instance label is wrong');
        $this->assertInternalType('string', $link, 'The method return should be a string');
        $this->assertEquals($link, $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK)), 'The instance link is wrong');
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


    public function testCreateTreeFromZip(){

        $dirs = array(
            'baseDir'   => array('child1', 'child2', 'child3'),
            'child2'    => array('child2.1', 'child2.2'),
            'child2.2'  => array('child2.2.1')
        );
        $base = 'baseDir';
        $parent = 'http://myFancyDomain.com/myGreatParentUri';

        $parentKeys = array('baseDir', 'child1','child2', 'child3', 'child2.1', 'child2.2', 'child2.2.1');

        $parents = $this->mediaService->createTreeFromZip($dirs,$base,$parent);

        //nothing missing in parents
        $missing = array_diff($parentKeys, array_keys($parents));
        //nothing more in parents
        $extra = array_diff(array_keys($parents), $parentKeys);


        //see if the return is ok
        $this->assertEmpty($missing, 'It miss '.implode(',', $missing).' in the return array');
        $this->assertEmpty($extra, 'there are extra values in return array : '.implode(',', $extra));


        $root = new \core_kernel_classes_Class($parent);

        $subclasses = $root->getSubClasses();
        /** @var \core_kernel_classes_Resource $baseClass */
        $baseClass = array_pop($subclasses);

        //see if base class is created
        $this->assertInstanceOf('\core_kernel_classes_Resource', $baseClass, 'It should create a class under the class : '.$parent);
        $this->assertEquals($base, $baseClass->getLabel(), 'The created class hasn\'t the right label');

        $subclasses = $baseClass->getSubClasses(true);
        $labels = array($base);
        /** @var \core_kernel_classes_Resource $subclass */
        foreach($subclasses as $subclass){
            $this->assertInstanceOf('\core_kernel_classes_Resource', $subclass, 'It should create a class under the class : '.$base);
            $labels[] = $subclass->getLabel();
        }

        //nothing missing in labels
        $missing = array_diff($parentKeys, $labels);
        //nothing more in labels
        $extra = array_diff($labels, $parentKeys);

        //see if subclasses are created
        $this->assertEmpty($missing, 'It miss '.implode(',', $missing).' in the created class');
        $this->assertEmpty($extra, 'there are extra classes created  : '.implode(',', $extra));
    }

}
 