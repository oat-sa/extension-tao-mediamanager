<?php

namespace oat\taoMediaManager\test\model;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\SimpleFileManagement;

include_once dirname(__FILE__) . '/../includes/raw_start.php';

class SimpleFileManagementTest extends TaoPhpUnitTestRunner {

    /**
     * @var SimpleFileManagement
     */
    private $fileManagement = null;

    public function setup(){
        TaoPhpUnitTestRunner::initTest();
        $this->fileManagement = new SimpleFileManagement();
    }

    public function testStoreFileValid(){

        $fileTmp = dirname(__FILE__).'/sample/Brazil.png';

        $this->assertFileNotExists(dirname(__DIR__).'/media/brazil.png', 'The file is already stored');
        $link = $this->fileManagement->storeFile($fileTmp);

        // test the return link
        $this->assertInternalType('string', $link, 'The method return should be a string');
        $this->assertEquals(dirname(__DIR__).'/media/brazil.png', $link, 'The link is wrong');
        $this->assertFileExists(dirname(__DIR__).'/media/brazil.png', 'The file has not been stored');

    }

    /**
     * @expectedException \common_exception_Error
     * @expectedExceptionMessage Unable to move uploaded file
     */
    public function testStoreFileException(){

        $fileTmp = dirname(__FILE__).'/sample/unknown.png';

        $this->fileManagement->storeFile($fileTmp);

    }


    public function testRetrieveFile(){

        $link = dirname(__DIR__).'/media/brazil.png';

        $file = $this->fileManagement->retrieveFile($link);

        // test the return link
        $this->assertInternalType('string', $file, 'The method return should be a string');
        $this->assertEquals($link, $file, 'The return file is wrong');
        $this->assertFileExists($file, 'The file is not stored');

    }

    public function testDeleteFile(){

        $link = dirname(__DIR__).'/media/brazil.png';

        $remove = $this->fileManagement->deleteFile($link);

        // test the return link
        $this->assertInternalType('boolean', $remove, 'The method return should be a string');
        $this->assertEquals(true, $remove, 'impossible to remove file');
        $this->assertFileNotExists($link, 'The file is still here');
    }

    public function testDeleteFileFail(){

        $link = dirname(__DIR__).'/media/brazil.png';

        $remove = $this->fileManagement->deleteFile($link);

        // test the return link
        $this->assertInternalType('boolean', $remove, 'The method return should be a string');
        $this->assertEquals(false, $remove, 'File was here');
    }


}
 