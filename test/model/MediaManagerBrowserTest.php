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

use oat\taoMediaManager\model\MediaManagerBrowser;


class MediaManagerBrowserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var mediaManagerBrowser
     */
    private $mediaManagerBrowser = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $fileManagerMock = null;


    private $rootClass = '';

    public function setUp()
    {
        $this->rootClass = 'http://myFancyDomaine.com/myGreatCLassUriForBrowserTest';
        $this->mediaManagerBrowser = new MediaManagerBrowser(array('lang' => 'EN_en', 'rootClass' => $this->rootClass));

        //fileManagerMock
        $this->fileManagerMock = $this->getMockBuilder('oat\taoMediaManager\model\fileManagement\SimpleFileManagement')
            ->getMock();

        $ref = new \ReflectionProperty('oat\taoMediaManager\model\fileManagement\FileManager', 'fileManager');
        $ref->setAccessible(true);
        $ref->setValue(null, $this->fileManagerMock);
    }

    public function tearDown()
    {
        $this->fileManagerMock = null;

        $ref = new \ReflectionProperty('oat\taoMediaManager\model\fileManagement\FileManager', 'fileManager');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
        $ref->setAccessible(false);

    }

    public function testGetDirectory()
    {

        $root = new \core_kernel_classes_Class($this->rootClass);

        //Remove what has been done
        $subclasses = $root->getSubClasses();
        foreach ($subclasses as $subclass) {
            $subclass->delete();
        }
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

        $labels = array();
        foreach ($newDirectory['children'] as $i => $child) {
            $this->assertInternalType('array', $child, 'The result should be an array');
            $this->assertArrayHasKey('label', $child, 'The result should contain "label"');
            $this->assertArrayHasKey('path', $child, 'The result should contain "path"');

            $labels[] = $child['label'];
        }
        $this->assertEquals(2, count($labels));
        $this->assertContains('mySubClass0', $labels);
        $this->assertContains('mySubClass1', $labels);
        
        //Remove what has been done
        $subclasses = $root->getSubClasses();
        foreach ($subclasses as $subclass) {
            $subclass->delete();
        }
        $root->delete();

    }

    public function testGetFileInfo()
    {

        $fileTmp = dirname(__DIR__) . '/sample/Brazil.png';

        $this->fileManagerMock->expects($this->once())
            ->method('retrieveFile')
            ->with('Brazil.png')
            ->willReturn($fileTmp);

        $fileInfo = $this->mediaManagerBrowser->getFileInfo('Brazil.png');

        $this->assertInternalType('array', $fileInfo, 'The result should be an array');
        $this->assertArrayHasKey('name', $fileInfo, 'The result should contain "name"');
        $this->assertArrayHasKey('mime', $fileInfo, 'The result should contain "mime"');
        $this->assertArrayHasKey('size', $fileInfo, 'The result should contain "size"');

        $this->assertEquals('Brazil.png', $fileInfo['name'], 'The file name is not correct');
        $this->assertEquals('image/png', $fileInfo['mime'], 'The mime type is not correct');
    }

    public function testGetFileInfoFail()
    {

        $link = 'A Fake link';

        $fileInfo = $this->mediaManagerBrowser->getFileInfo($link);

        $this->assertNull($fileInfo, 'The result should be null');
    }

}
 