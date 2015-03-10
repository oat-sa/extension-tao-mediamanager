<?php

namespace oat\taoMediaManager\test\model;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\fileManagement\SimpleFileManagement;

include_once dirname(__FILE__) . '/../../includes/raw_start.php';

class SimpleFileManagementTest extends TaoPhpUnitTestRunner {

    /**
     * @var SimpleFileManagement
     */
    private $fileManagement = null;
    
    private $storageDir;

    public function setup(){
        TaoPhpUnitTestRunner::initTest();
        $this->fileManagement = new SimpleFileManagement();
        $this->storageDir = dirname(dirname(__DIR__)).'/media/';
    }

    public function testStoreFileValid(){

        $fileTmp = dirname(__DIR__).'/sample/Brazil.png';

        $this->assertFileNotExists($this->storageDir.'brazil.png', 'The file is already stored');
        $link = $this->fileManagement->storeFile($fileTmp);

        // test the return link
        $this->assertInternalType('string', $link, 'The method return should be a string');
        $this->assertEquals('brazil.png', $link, 'The link is wrong');
        $this->assertFileExists($this->storageDir.'brazil.png', 'The file has not been stored');
        
        return $link;
    }

    /**
     * @depends testStoreFileValid
     */ 
    public function testRetrieveFile($link) {

        $storage = $this->storageDir.$link;
        $file = $this->fileManagement->retrieveFile($link);

        // test the return link
        $this->assertInternalType('string', $file, 'The method return should be a string');
        $this->assertEquals($storage, $file, 'The return file is wrong');
        $this->assertFileExists($file, 'The file is not stored');

        return $link;
    }

    /**
     * @depends testRetrieveFile
     */
    public function testDeleteFile($link)
    {
        
        $remove = $this->fileManagement->deleteFile($link);

        // test the return link
        $this->assertInternalType('boolean', $remove, 'The method return should be a string');
        $this->assertEquals(true, $remove, 'impossible to remove file');
        $this->assertFileNotExists($this->storageDir.$link, 'The file is still here');
    }

    public function testDeleteFileFail(){

        $link = 'notadir/notafile.png';

        $remove = $this->fileManagement->deleteFile($link);

        // test the return link
        $this->assertInternalType('boolean', $remove, 'The method return should be a string');
        $this->assertEquals(false, $remove, 'File was not removed');
    }


}
 