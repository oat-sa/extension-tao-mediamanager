<?php

/**
 * SPDX-FileCopyrightText: 2017-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

namespace oat\taoMediaManager\test\integration\model;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use core_kernel_persistence_smoothsql_SmoothModel;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\mapper\MediaSourcePermissionsMapper;
use oat\taoMediaManager\model\TaoMediaOntology;
use PHPUnit\Framework\TestCase;
use tao_helpers_Uri;
use tao_models_classes_FileNotFoundException;
use GuzzleHttp\Psr7\Stream;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;

/**
 * Class MediaSourceTest
 * @package oat\taoMediaManager\test\model
 * @author Aleh Hutnikau, <goodnickoff@gmail.com>
 */
class MediaSourceTest extends TestCase
{
    private Prophet $prophet;

    protected function setUp(): void
    {
        $this->prophet = new Prophet();
    }

    protected function tearDown(): void
    {
        $this->prophet->checkPredictions();
    }

    public function testAdd()
    {
        $parent = 'class-uri-fixture';
        $label = 'label-fixture';
        $mime = 'mime-fixture';
        $size = '123456';
        $link = 'link-fixture';

        $createdResourceUri = 'uri-created-fixture';

        $filePath = dirname(__DIR__) . '/sample/Italy.png';

        $mediaSource = new MediaSource([
            'rootClass' => $parent,
            'lang' => 'lang-fixture',
        ]);
        $this->injectPermissionsMapper($mediaSource);

        $mediaServiceProphecy = $this->prophet->prophesize(MediaService::class);
        $mediaServiceProphecy->createMediaInstance(
            $filePath,
            'uri-fixture',
            'lang-fixture',
            'Italy1.png',
            null
        )->willReturn($createdResourceUri);

        $ref = new ReflectionProperty(MediaSource::class, 'mediaService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $mediaServiceProphecy->reveal());

        $fileManagementProphecy = $this->prophet->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize($link)->willReturn($size);

        $ref = new ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $classMock = $this->prophet->prophesize(\core_kernel_classes_Class::class);
        $classMock->getUri()->willReturn('uri-fixture');

        $resourceProphecy = $this->prophet->prophesize(core_kernel_classes_Resource::class);
        $resourceProphecy->exists()->willReturn(true);
        $resourceProphecy->getUniquePropertyValue(Argument::any())->willReturn($link, $mime);
        $resourceProphecy->getPropertyValues(Argument::any())->willReturn([]);
        $resourceProphecy->getPropertiesValues(Argument::any())->willReturn(
            [
                TaoMediaOntology::PROPERTY_LINK => [$link],
                TaoMediaOntology::PROPERTY_MIME_TYPE => [$mime],
                TaoMediaOntology::PROPERTY_ALT_TEXT => [$size],
            ]
        );
        $resourceProphecy->getLabel()->willReturn($label);
        $resourceProphecy->getUri()->willReturn('uri');

        $linkPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $mimePropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $altTextPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);

        $modelMock = $this->prophet->prophesize(core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getClass('class-uri-fixture')->willReturn($classMock->reveal());
        $modelMock->getResource($createdResourceUri)->willReturn($resourceProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_LINK)->willReturn($linkPropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_MIME_TYPE)->willReturn($mimePropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_ALT_TEXT)->willReturn($altTextPropertyProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

        $success = $mediaSource->add($filePath, 'Italy1.png', '');

        // has no error
        $this->assertIsArray($success, 'Should be a file info array');
        $this->assertArrayNotHasKey('error', $success, 'upload doesn\'t succeed');

        $this->assertEquals($label, $success['name']);
        $this->assertArrayHasKey('uri', $success);
        $this->assertEquals($mime, $success['mime']);
        $this->assertEquals($size, $success['size']);
        $this->assertEquals($link, $success['link']);

        $resourceUri = tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $success['uri']));
        $this->assertEquals($createdResourceUri, $resourceUri);
    }

    /**
     * @dataProvider mediaIdsProvider
     */
    public function testGetFileInfo(string $resourceId, string $searchId)
    {
        $parent = 'class-uri-fixture';
        $label = 'label-fixture';
        $mime = 'mime-fixture';
        $size = '123456';
        $link = 'link-fixture';

        $mediaSource = new MediaSource([
            'rootClass' => $parent,
            'lang' => 'lang-fixture',
        ]);
        $this->injectPermissionsMapper($mediaSource);

        $fileManagementProphecy = $this->prophet->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize($link)->willReturn($size);

        $ref = new ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $classMock = $this->prophet->prophesize(\core_kernel_classes_Class::class);
        $classMock->getUri()->willReturn('uri-fixture');

        $resourceProphecy = $this->prophet->prophesize(core_kernel_classes_Resource::class);
        $resourceProphecy->exists()->willReturn(true);
        $resourceProphecy->getLabel()->willReturn($label);
        $resourceProphecy->getUri()->willReturn('uri');
        $resourceProphecy->getUniquePropertyValue(Argument::any())->willReturn($link, $mime);
        $resourceProphecy->getPropertyValues(Argument::any())->willReturn([]);
        $resourceProphecy->getPropertiesValues(Argument::any())->willReturn(
            [
                TaoMediaOntology::PROPERTY_LINK => [$link],
                TaoMediaOntology::PROPERTY_MIME_TYPE => [$mime],
                TaoMediaOntology::PROPERTY_ALT_TEXT => [$size],
            ]
        );


        $linkPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $mimePropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $altTextPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);

        $modelMock = $this->prophet->prophesize(core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getClass('class-uri-fixture')->willReturn($classMock->reveal());
        $modelMock->getResource($resourceId)->willReturn($resourceProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_LINK)->willReturn($linkPropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_MIME_TYPE)->willReturn($mimePropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_ALT_TEXT)->willReturn($altTextPropertyProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

        $success = $mediaSource->getFileInfo($searchId);

        $this->assertEquals($label, $success['name']);
        $this->assertArrayHasKey('uri', $success);
        $this->assertEquals($mime, $success['mime']);
        $this->assertEquals($size, $success['size']);
        $this->assertEquals($link, $success['link']);
    }

    public function mediaIdsProvider(): array
    {
        return [
            [
                'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5',
                'taomedia://mediamanager/https_2_test-tao-deploy_0_docker_0_localhost_1_ontologies_1_tao_0_rdf_3_i5'
            ],
            [
                'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5',
                'https_2_test-tao-deploy_0_docker_0_localhost_1_ontologies_1_tao_0_rdf_3_i5'
            ],
            [
                'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5',
                'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i5'
            ],
        ];
    }

    public function testUploadFail()
    {
        $this->expectException(tao_models_classes_FileNotFoundException::class);
        $this->expectExceptionMessageMatches('/File [^\s]+ not found/');

        $filePath = dirname(__DIR__) . '/sample/Unknown.png';
        $mediaSource = new MediaSource();
        $mediaSource->add($filePath, 'Unknown.png', "");
    }

    public function testDelete()
    {
        $uri = 'test';
        $mediaSource = new MediaSource();

        $mediaServiceProphecy = $this->prophet->prophesize(MediaService::class);
        $mediaServiceProphecy->deleteResource(Argument::that(function ($resource) {
            return $resource instanceof core_kernel_classes_Resource;
        }))->willReturn(true);

        $ref = new ReflectionProperty(MediaSource::class, 'mediaService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $mediaServiceProphecy->reveal());

        $resourceProphecy = $this->prophet->prophesize(core_kernel_classes_Resource::class);

        $modelMock = $this->prophet->prophesize(core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getResource($uri)->willReturn($resourceProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

        $success = $mediaSource->delete($uri);
        $this->assertTrue($success, 'The file is not deleted');
    }

    public function testGetDirectory()
    {
        $filePath = dirname(__DIR__) . '/sample/Italy.png';
        $mediaSource = new MediaSource([
            'rootClass' => MediaService::ROOT_CLASS_URI,
        ]);
        $this->injectPermissionsMapper($mediaSource);
        $createdResourceUri = 'uri-created-fixture';

        $fileManagementProphecy = $this->prophet->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize(Argument::any())->willReturn(100);

        $ref = new ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $mediaServiceProphecy = $this->prophet->prophesize(MediaService::class);
        $mediaServiceProphecy->createMediaInstance(
            $filePath,
            'test',
            '',
            'Italy1.png',
            'test/mime'
        )->willReturn($createdResourceUri);

        $ref = new ReflectionProperty(MediaSource::class, 'mediaService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $mediaServiceProphecy->reveal());

        $directoryClassProphecy = $this->prophet->prophesize(\core_kernel_classes_Class::class);
        $directoryClassProphecy->getUri()->willReturn('test');
        $directoryClassProphecy->getLabel()->willReturn('test');
        $directoryClassProphecy->isSubClassOf(Argument::type(\core_kernel_classes_Class::class))->willReturn(true);
        $directoryClassProphecy->exists()->willReturn(true);
        $directoryClassProphecy->getSubClasses()->willReturn([]);
        $directoryClassProphecy->searchInstances([], [])->willReturn([]);
        $directoryClassProphecy->countInstances([])->willReturn(0);

        $rootClassProphecy = $this->prophet->prophesize(\core_kernel_classes_Class::class);
        $rootClassProphecy->getUri()->willReturn(MediaService::ROOT_CLASS_URI);

        $resourceProphecy = $this->prophet->prophesize(core_kernel_classes_Resource::class);
        $resourceProphecy->exists()->willReturn(true);
        $resourceProphecy->getPropertiesValues(Argument::any())->willReturn(
            [
                TaoMediaOntology::PROPERTY_LINK => ['link-fixture'],
                TaoMediaOntology::PROPERTY_MIME_TYPE => ['test/mime'],
                TaoMediaOntology::PROPERTY_ALT_TEXT => ['Italy1.png'],
            ]
        );
        $resourceProphecy->getLabel()->willReturn('Italy1.png');
        $resourceProphecy->getUri()->willReturn($createdResourceUri);

        $linkPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $mimePropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $altTextPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);

        $modelMock = $this->prophet->prophesize(core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getClass(MediaService::ROOT_CLASS_URI)->willReturn($rootClassProphecy->reveal());
        $modelMock->getClass('test')->willReturn($directoryClassProphecy->reveal());
        $modelMock->getResource($createdResourceUri)->willReturn($resourceProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_LINK)->willReturn($linkPropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_MIME_TYPE)->willReturn($mimePropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_ALT_TEXT)->willReturn($altTextPropertyProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

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
        $mediaSource = new MediaSource([
            'rootClass' => MediaService::ROOT_CLASS_URI,
        ]);
        $this->injectPermissionsMapper($mediaSource);
        $createdResourceUri = 'uri-created-fixture';
        $fileLink = 'link-fixture';

        $fileManagementProphecy = $this->prophet->prophesize(FileManagement::class);
        $fileManagementProphecy->getFileSize(Argument::any())->willReturn(filesize($filePath));
        $fileManagementProphecy->getFileStream($fileLink)->willReturn(new Stream($resource));

        $ref = new ReflectionProperty(MediaSource::class, 'fileManagementService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $fileManagementProphecy->reveal());

        $mediaServiceProphecy = $this->prophet->prophesize(MediaService::class);
        $mediaServiceProphecy->createMediaInstance(
            $filePath,
            MediaService::ROOT_CLASS_URI,
            '',
            'Italy1.png',
            null
        )->willReturn($createdResourceUri);

        $ref = new ReflectionProperty(MediaSource::class, 'mediaService');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $mediaServiceProphecy->reveal());

        $rootClassProphecy = $this->prophet->prophesize(\core_kernel_classes_Class::class);
        $rootClassProphecy->getUri()->willReturn(MediaService::ROOT_CLASS_URI);

        $resourceProphecy = $this->prophet->prophesize(core_kernel_classes_Resource::class);
        $resourceProphecy->exists()->willReturn(true);
        $resourceProphecy->getPropertiesValues(Argument::any())->willReturn(
            [
                TaoMediaOntology::PROPERTY_LINK => [$fileLink],
                TaoMediaOntology::PROPERTY_MIME_TYPE => ['image/png'],
                TaoMediaOntology::PROPERTY_ALT_TEXT => ['Italy1.png'],
            ]
        );
        $resourceProphecy->getOnePropertyValue(Argument::any())->willReturn($fileLink);
        $resourceProphecy->getLabel()->willReturn('Italy1.png');
        $resourceProphecy->getUri()->willReturn($createdResourceUri);

        $linkPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $mimePropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);
        $altTextPropertyProphecy = $this->prophet->prophesize(core_kernel_classes_Property::class);

        $modelMock = $this->prophet->prophesize(core_kernel_persistence_smoothsql_SmoothModel::class);
        $modelMock->getClass(MediaService::ROOT_CLASS_URI)->willReturn($rootClassProphecy->reveal());
        $modelMock->getResource($createdResourceUri)->willReturn($resourceProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_LINK)->willReturn($linkPropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_MIME_TYPE)->willReturn($mimePropertyProphecy->reveal());
        $modelMock->getProperty(TaoMediaOntology::PROPERTY_ALT_TEXT)->willReturn($altTextPropertyProphecy->reveal());

        $mediaSource->setModel($modelMock->reveal());

        $info = $mediaSource->add($filePath, 'Italy1.png', '');

        $resourceUri = tao_helpers_Uri::decode(str_replace(MediaSource::SCHEME_NAME, '', $info['uri']));
        $stream = $mediaSource->getFileStream($resourceUri);

        $this->assertTrue($stream instanceof StreamInterface);
        $this->assertEquals($info['size'], $stream->getSize());

        fclose($resource);
    }

    private function injectPermissionsMapper(MediaSource $mediaSource): void
    {
        $permissionsMapperProphecy = $this->prophet->prophesize(MediaSourcePermissionsMapper::class);
        $permissionsMapperProphecy->map(Argument::type('array'), Argument::type('string'))
            ->will(function (array $args): array {
                return $args[0];
            });

        $ref = new ReflectionProperty(MediaSource::class, 'permissionsMapper');
        $ref->setAccessible(true);
        $ref->setValue($mediaSource, $permissionsMapperProphecy->reveal());
    }
}
