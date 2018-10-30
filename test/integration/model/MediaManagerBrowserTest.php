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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoMediaManager\test\integration\model;

use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\MediaService;

class MediaManagerBrowserTest extends \PHPUnit_Framework_TestCase
{
    private $rootClass = '';

    public function setUp()
    {
        $this->rootClass = new \core_kernel_classes_Class('http://myFancyDomaine.com/myGreatCLassUriForBrowserTest');
    }

    public function tearDown()
    {
        (MediaService::singleton())->deleteClass($this->rootClass);
    }

    public function testGetDirectory()
    {
        $this->rootClass->setLabel('myRootClass');

        $acceptableMime = array();
        $depth = 1;

        $mediaSource = $this->initializeMediaSource();

        $directory = $mediaSource->getDirectory(\tao_helpers_Uri::encode($this->rootClass->getUri()), $acceptableMime, $depth);

        $this->assertInternalType('array', $directory, 'The result should be an array');
        $this->assertArrayHasKey('label', $directory, 'The result should contain "label"');
        $this->assertArrayHasKey('path', $directory, 'The result should contain "path"');
        $this->assertArrayHasKey('children', $directory, 'The result should contain "children"');

        $this->assertInternalType('array', $directory['children'], 'Children should be an array');
        $this->assertEquals('myRootClass', $directory['label'], 'The label is not correct');
        $this->assertEquals('taomedia://mediamanager/' . \tao_helpers_Uri::encode($this->rootClass->getUri()), $directory['path'], 'The path is not correct');

        $this->rootClass->createSubClass('mySubClass1');
        $this->rootClass->createSubClass('mySubClass0');

        $newDirectory = $mediaSource->getDirectory(\tao_helpers_Uri::encode($this->rootClass->getUri()), $acceptableMime, $depth);
        $this->assertInternalType('array', $newDirectory['children'], 'Children should be an array');
        $this->assertNotEmpty($newDirectory['children'], 'Children should not be empty');

        $labels = array();

        foreach ($newDirectory['children'] as $i => $child) {
            $this->assertInternalType('array', $child, 'The result should be an array');
            if (isset($child['parent'])) {
                $this->assertArrayHasKey('label', $child, 'The result should contain "label"');
                $this->assertArrayHasKey('path', $child, 'The result should contain "path"');
                $labels[$child['label']] = $child['label'];
            } else {
                $this->assertArrayHasKey('name', $child, 'The result should contain "name"');
                $this->assertArrayHasKey('uri', $child, 'The result should contain "uri"');
                $this->assertArrayHasKey('link', $child, 'The result should contain "link"');
            }
        }
        $this->assertEquals(2, count($labels));
        $this->assertContains('mySubClass0', $labels);
        $this->assertContains('mySubClass1', $labels);
    }

    public function testGetFileInfo()
    {
        $instance = $this->rootClass->createInstance('Brazil.png');
        $instance->setPropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK), 'myGreatLink');
        $instance->setPropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_MIME_TYPE), 'image/png');

        $uri = $instance->getUri();

        $fileInfo = $this->initializeMediaSource()->getFileInfo(\tao_helpers_Uri::decode($uri));

        $this->assertInternalType('array', $fileInfo, 'The result should be an array');
        $this->assertArrayHasKey('name', $fileInfo, 'The result should contain "name"');
        $this->assertArrayHasKey('mime', $fileInfo, 'The result should contain "mime"');
        $this->assertArrayHasKey('size', $fileInfo, 'The result should contain "size"');
        $this->assertArrayHasKey('uri', $fileInfo, 'The result should contain "size"');

        $this->assertEquals($instance->getLabel(), $fileInfo['name'], 'The file name is not correct');
        $this->assertEquals('image/png', $fileInfo['mime'], 'The mime type is not correct');
        $this->assertEquals('taomedia://mediamanager/' . \tao_helpers_Uri::encode($uri), $fileInfo['uri'], 'The uri is not correct');
    }

    /**
     * @expectedException        \tao_models_classes_FileNotFoundException
     * @expectedExceptionMessage File A Fake link not found
     */
    public function testGetFileInfoFail()
    {
        $link = 'A Fake link';
        $this->initializeMediaSource()->getFileInfo($link);
    }

    private function initializeMediaSource()
    {
        $fileManagerMock = $this->getMockBuilder(FlySystemManagement::class)
            ->setMethods(array('getFileSize', 'deleteFile'))
            ->getMock();

        $fileManagerMock->expects($this->any())
            ->method('getFileSize')
            ->willReturn(100);

        $mediaSource = new MediaSource(array('lang' => 'EN_en', 'rootClass' => $this->rootClass));

        $ref = new \ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagerMock);

        return $mediaSource;
    }

}
 