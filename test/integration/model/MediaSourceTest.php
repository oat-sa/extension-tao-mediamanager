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

use GuzzleHttp\Psr7\Stream;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use Prophecy\Argument;
use Psr\Http\Message\StreamInterface;

/**
 * Class MediaSourceTest
 * @package oat\taoMediaManager\test\model
 * @author Aleh Hutnikau, <goodnickoff@gmail.com>
 */
class MediaSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $parent = 'class-uri-fixture';
        $label = 'label-fixture';
        $mime = 'mime-fixture';
        $size = '123456';
        $link = 'link-fixture';

        $createdResourceUri = 'uri-created-fixture';

        $filePath = dirname(__DIR__) . '/sample/Italy.png';

        $mediaSource = new MediaSource(array(
            'rootClass' => $parent,
            'lang' => 'lang-fixture',
        ));

        $mediaServiceProphecy = $this->prophesize(MediaService::class);
        $mediaServiceProphecy->createMediaInstance(
            $filePath, 'uri-fixture', 'lang-fixture', 'Italy1.png', null
        )->willReturn($createdResourceUri);

        $ref = new \ReflectionProperty(MediaSource::class, 'mediaService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $mediaServiceProphecy->reveal());

        $fileManagementProphecy = $this->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize($link)->willReturn($size);

        $ref = new \ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $classMock = $this->prophesize(\core_kernel_classes_Class::class);
        $classMock->getUri()->willReturn('uri-fixture');

        $resourceProphecy = $this->prophesize(\core_kernel_classes_Resource::class);
        $resourceProphecy->exists()->willReturn(true);
        $resourceProphecy->getUniquePropertyValue(Argument::any())->willReturn($link, $mime);
        $resourceProphecy->getPropertyValues(Argument::any())->willReturn(0);
        $resourceProphecy->getLabel()->willReturn($label);

        $linkPropertyProphecy = $this->prophesize(\core_kernel_classes_Property::class);
        $mimePropertyProphecy = $this->prophesize(\core_kernel_classes_Property::class);
        $altTextPropertyProphecy = $this->prophesize(\core_kernel_classes_Property::class);

        $modelMock = $this->prophesize(\core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getClass('class-uri-fixture')->willReturn($classMock->reveal());
        $modelMock->getResource($createdResourceUri)->willReturn($resourceProphecy->reveal());
        $modelMock->getProperty(MediaService::PROPERTY_LINK)->willReturn($linkPropertyProphecy->reveal());
        $modelMock->getProperty(MediaService::PROPERTY_MIME_TYPE)->willReturn($mimePropertyProphecy->reveal());
        $modelMock->getProperty(MediaService::PROPERTY_ALT_TEXT)->willReturn($altTextPropertyProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

        $success = $mediaSource->add($filePath, 'Italy1.png', '');

        // has no error
        $this->assertInternalType('array', $success, 'Should be a file info array');
        $this->assertArrayNotHasKey('error', $success, 'upload doesn\'t succeed');

        $this->assertEquals($label, $success['name']);
        $this->assertArrayHasKey('uri', $success);
        $this->assertEquals($mime, $success['mime']);
        $this->assertEquals($size, $success['size']);
        $this->assertEquals($link, $success['link']);

        $resourceUri = \tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $success['uri']));
        $this->assertEquals($createdResourceUri, $resourceUri);
    }

    /**
     * @expectedException \tao_models_classes_FileNotFoundException
     * @expectedExceptionMessageRegExp /File [^\s]+ not found/
     */
    public function testUploadFail()
    {
        $filePath = dirname(__DIR__) . '/sample/Unknown.png';
        $mediaSource = new MediaSource();
        $mediaSource->add($filePath, 'Unknown.png', "");
    }

    public function testDelete()
    {
        $uri = 'test';
        $mediaSource = new MediaSource();

        $mediaServiceProphecy = $this->prophesize(MediaService::class);
        $mediaServiceProphecy->deleteResource(Argument::that(function ($resource) {
            return $resource instanceof \core_kernel_classes_Resource;
        }))->willReturn(true);

        $ref = new \ReflectionProperty(MediaSource::class, 'mediaService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $mediaServiceProphecy->reveal());

        $resourceProphecy = $this->prophesize(\core_kernel_classes_Resource::class);

        $modelMock = $this->prophesize(\core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getResource($uri)->willReturn($resourceProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

        $success = $mediaSource->delete($uri);
        $this->assertTrue($success, 'The file is not deleted');
    }

    public function testGetDirectory()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';
        $mediaSource = new MediaSource();

        $fileManagementProphecy = $this->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize(Argument::any())->willReturn(100);

        $ref = new \ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $success = $mediaSource->add($filePath, 'Italy1.png', 'test', 'test/mime');
        $directory = $mediaSource->getDirectory('test');
        $this->assertTrue(is_array($directory));
        $this->assertEquals('test/mime', $success['mime']);
        $this->assertEquals('taomedia://mediamanager/test', $directory['path']);
    }

    public function testGetFileStream()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';
        $resource = fopen($filePath, 'r');
        $mediaSource = new MediaSource();

        $fileManagementProphecy = $this->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize(Argument::any())->willReturn(filesize($filePath));
        $fileManagementProphecy->getFileStream(Argument::any())->willReturn(new Stream($resource));

        $ref = new \ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $info = $mediaSource->add($filePath, 'Italy1.png', '');

        $resourceUri = \tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $info['uri']));
        $stream = $mediaSource->getFileStream($resourceUri);

        $this->assertTrue($stream instanceof StreamInterface);
        $this->assertEquals($info['size'], $stream->getSize());

        fclose($resource);
    }
}
 