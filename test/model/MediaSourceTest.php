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

use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\filesystem\FileSystemService;

/**
 * Class MediaSourceTest
 * @package oat\taoMediaManager\test\model
 * @author Aleh Hutnikau, <goodnickoff@gmail.com>
 */
class MediaSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var MediaSource */
    private $mediaSource = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $service = null;

    private $classUri = null;

    public function setUp()
    {
        $rootClass = MediaService::singleton()->getRootClass();
        $this->classUri = $rootClass->createSubClass('great', 'comment')->getUri();
        $this->service = MediaService::singleton();
        $this->mediaSource = new MediaSource();

        $fileManager = ServiceManager::getServiceManager()->get(FileManagement::SERVICE_ID);
        $fileManager->setOptions([
            'fs' => 'testFs'
        ]);
        $fs = ServiceManager::getServiceManager()->get(FileSystemService::SERVICE_ID);
        $fs->setOptions([
            'filesPath' => dirname(__DIR__) . '\\sample',
            'adapters' => [
                'testFs' => [
                    'class' => 'Local',
                    'options' => [
                        'root' => dirname(__DIR__) . '\\sample\\fs'
                    ]
                ]
            ]
        ]);
    }

    public function tearDown()
    {
        MediaService::singleton()->deleteClass(new \core_kernel_classes_Class($this->classUri));
    }

    public function testAdd()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';

        $success = $this->mediaSource->add($filePath, 'Italy1.png', $this->classUri);

        // has no error
        $this->assertInternalType('array', $success, 'Should be a file info array');
        $this->assertArrayNotHasKey('error', $success, 'upload doesn\'t succeed');
        $this->assertEquals('Italy1.png', $success['name']);
        $this->assertArrayHasKey('uri', $success);
        $resourceUri = \tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $success['uri']));

        $this->assertTrue((new \core_kernel_classes_Resource($resourceUri))->exists());
    }

    /**
     * @expectedException \tao_models_classes_FileNotFoundException
     * @expectedExceptionMessageRegExp /File [^\s]+ not found/
     */
    public function testUploadFail()
    {
        $filePath = dirname(__DIR__) . '/sample/Unknown.png';
        $this->mediaSource->add($filePath, 'Unknown.png', $this->classUri);
    }

    public function testDelete()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';
        $info = $this->mediaSource->add($filePath, 'Italy1.png', $this->classUri);
        $resourceUri = \tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $info['uri']));
        $instance = new \core_kernel_classes_Resource($resourceUri);
        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'This class should exists');
        $success = $this->mediaSource->delete($resourceUri);

        // should return true
        $this->assertTrue($success, 'The file is not deleted');
        // should remove the instance
        $removedInstance = new \core_kernel_classes_Class($instance->getUri());
        $this->assertFalse($instance->exists(), 'The instance still exists');
        $this->assertFalse($removedInstance->exists(), 'The instance still exists');
    }

    public function testGetDirectory()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';
        $success = $this->mediaSource->add($filePath, 'Italy1.png', $this->classUri);
        $directory = $this->mediaSource->getDirectory($this->classUri);
        $this->assertTrue(is_array($directory));
        $this->assertArrayHasKey('children', $directory);
        $this->assertEquals(1, count($directory['children']));
        $this->assertEquals($success, $directory['children'][0]);
    }

    public function testGetFileStream()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';
        $info = $this->mediaSource->add($filePath, 'Italy1.png', $this->classUri);
        $resourceUri = \tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $info['uri']));
        $stream = $this->mediaSource->getFileStream($resourceUri);
        $this->assertTrue($stream instanceof \Psr\Http\Message\StreamInterface);
        $this->assertEquals($info['size'], $stream->getSize());
    }
}
 