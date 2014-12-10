<?php

namespace oat\taoMediaManager\test\model;



use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\fileManagement\FileManager;
use oat\taoMediaManager\model\MediaManagerBrowser;

include_once dirname(__FILE__) . '/../../includes/raw_start.php';

class MediaManagerBrowserTest extends TaoPhpUnitTestRunner {

    /**
     * @var mediaManagerBrowser
     */
    private $mediaManagerBrowser = null;

    private $rootClass = '';

    public function setup(){
        TaoPhpUnitTestRunner::initTest();
        $this->rootClass = 'http://myFancyDomaine.com/myGreatCLassUriForBrowserTest';
        $this->mediaManagerBrowser = new MediaManagerBrowser(array('lang' => 'EN_en', 'rootClass' => $this->rootClass));
    }

    public function testGetDirectory(){

        $root = new \core_kernel_classes_Class($this->rootClass);
        $root->delete();
        $root->setLabel('myRootClass');

        $acceptableMime = array();
        $depth = 1;

        $directory = $this->mediaManagerBrowser->getDirectory('/', $acceptableMime, $depth);

        $this->assertInternalType('array', $directory, 'The result should be an array');
        $this->assertArrayHasKey('label', $directory, 'The result should contain "label"');
        $this->assertArrayHasKey('path', $directory, 'The result should contain "path"');
        $this->assertArrayHasKey('children', $directory, 'The result should contain "children"');

        $this->assertInternalType('array', $directory['children'], 'Children should be an array');
        $this->assertEmpty($directory['children'], 'Children should be empty');
        $this->assertEquals('myRootClass', $directory['label'], 'The label is not correct');
        $this->assertEquals('mediamanager/', $directory['path'], 'The path is not correct');

        $root->createSubClass('mySubClass1');
        $root->createSubClass('mySubClass0');

        $newDirectory = $this->mediaManagerBrowser->getDirectory('/', $acceptableMime, $depth);
        $this->assertInternalType('array', $newDirectory['children'], 'Children should be an array');
        $this->assertNotEmpty($newDirectory['children'], 'Children should be empty');

        foreach($newDirectory['children'] as $i => $child){
            $this->assertInternalType('array', $child, 'The result should be an array');
            $this->assertArrayHasKey('label', $child, 'The result should contain "label"');
            $this->assertArrayHasKey('path', $child, 'The result should contain "path"');

            $this->assertEquals('mySubClass'.$i, $child['label'], 'The label is not correct');
        }


        //Remove what has been done
        $subclasses = $root->getSubClasses();
        foreach($subclasses as $subclass){
            $subclass->delete();
        }
        $root->delete();

    }

    public function testGetFileInfo(){

        $fileManager = FileManager::getFileManagementModel();
        $fileTmp = dirname(__DIR__).'/sample/Brazil.png';
        $link = $fileManager->storeFile($fileTmp);
        $acceptableMime = array();

        $fileInfo = $this->mediaManagerBrowser->getFileInfo($link, $acceptableMime);

        $this->assertInternalType('array', $fileInfo, 'The result should be an array');
        $this->assertArrayHasKey('name', $fileInfo, 'The result should contain "name"');
        $this->assertArrayHasKey('mime', $fileInfo, 'The result should contain "mime"');
        $this->assertArrayHasKey('size', $fileInfo, 'The result should contain "size"');
        $this->assertArrayHasKey('url', $fileInfo, 'The result should contain "url"');

        $this->assertEquals('brazil.png', $fileInfo['name'], 'The file name is not correct');
        $this->assertEquals('image/png', $fileInfo['mime'], 'The mime type is not correct');
        $this->assertContains('taoItems/ItemContent/download?path=mediamanager'.urlencode($link), $fileInfo['url'], 'The url is not correct');

        //remove what has been done
        $fileManager->deleteFile($link);


    }

    public function testGetFileInfoFail(){

        $link = 'A Fake link';
        $acceptableMime = array();

        $fileInfo = $this->mediaManagerBrowser->getFileInfo($link, $acceptableMime);

        $this->assertNull($fileInfo, 'The result should be null');
    }

}
 