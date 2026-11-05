<?php

/**
 * SPDX-FileCopyrightText: 2014-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\integration\model;

use oat\oatbox\service\ServiceManager;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaService;
use oat\generis\test\TestCase;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_classes_Property as RdfProperty;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoRevision\model\RepositoryService;

class MediaServiceTest extends TestCase
{
    /**
     * @var \core_kernel_classes_Class
     */
    private $testClass = null;

    public function setUp(): void
    {
        $this->testClass = (MediaService::singleton())->getRootClass()->createSubClass('test class');

        $revisionService = $this->createMock(RepositoryService::class);
        $revisionService->method('commit');

        $serviceManager = ServiceManager::getServiceManager();
        $serviceManager->overload(RepositoryService::SERVICE_ID, $revisionService);
    }

    public function tearDown(): void
    {
        (MediaService::singleton())->deleteClass($this->testClass);
    }

    public function testGetRootClass()
    {
        $this->assertEquals(
            'http://www.tao.lu/Ontologies/TAOMedia.rdf#Media',
            (MediaService::singleton())->getRootClass()->getUri(),
            'The root class of the service is not correct'
        );
    }

    private function initializeMockForCreateInstance($fileTmp)
    {
        $fileManagerMock = $this->getMockBuilder(FlySystemManagement::class)
            ->setMethods(['storeFile', 'deleteFile'])
            ->getMock();

        $fileManagerMock->expects($this->once())
            ->method('storeFile')
            ->with($fileTmp)
            ->willReturn('MyGreatLink');

        $serviceManager = ServiceManager::getServiceManager();
        $serviceManager->overload(FlySystemManagement::SERVICE_ID, $fileManagerMock);

        $mediaService = new MediaService();
        $mediaService->setServiceLocator($serviceManager);

        return $mediaService;
    }

    private function initializeMockForEditInstance($fileTmp)
    {
        $fileManagerMock = $this->getMockBuilder(FlySystemManagement::class)
            ->setMethods(['storeFile', 'deleteFile'])
            ->getMock();

        $fileManagerMock->expects($this->once())
            ->method('deleteFile')
            ->with($fileTmp)
            ->willReturn(true);

        $serviceManager = ServiceManager::getServiceManager();
        $serviceManager->overload(FlySystemManagement::SERVICE_ID, $fileManagerMock);

        $mediaService = new MediaService();
        $mediaService->setServiceLocator($serviceManager);

        return $mediaService;
    }

    public function testCreateMediaInstance()
    {
        $fileTmp = dirname(__DIR__) . '/sample/Brazil.png';
        $lang = 'EN-en';
        $classUri = $this->testClass->getUri();

        $mediaService = $this->initializeMockForCreateInstance($fileTmp);
        $uri = $mediaService->createMediaInstance($fileTmp, $classUri, $lang);

        $instance = new RdfResource($uri);
        $thing = $instance->getUniquePropertyValue(
            new RdfProperty(TaoMediaOntology::PROPERTY_LINK)
        );

        $linkResult = $thing instanceof RdfResource ? $thing->getUri() : (string)$thing;
        $this->assertInstanceOf(
            '\core_kernel_classes_Resource',
            $instance,
            'It should create an instance under the class in parameter'
        );
        $this->assertEquals('Brazil.png', $instance->getLabel(), 'The instance label is wrong');
        $this->assertIsString($uri, 'The method return should be a string');
        $this->assertEquals($linkResult, 'MyGreatLink', 'The returned link is wrong');
        $this->assertEquals(
            $lang,
            $instance->getUniquePropertyValue(
                new RdfProperty(TaoMediaOntology::PROPERTY_LANGUAGE)
            ),
            'The instance language is wrong'
        );

        $this->assertTrue($instance->delete(true));
    }

    public function testEditMediaInstance()
    {
        $fileTmp = dirname(__DIR__) . '/sample/Italy.png';
        $lang = 'EN-en';

        $linkProperty = new RdfProperty(TaoMediaOntology::PROPERTY_LINK);
        $mimeTypeProperty = new RdfProperty(TaoMediaOntology::PROPERTY_MIME_TYPE);

        $instance = $this->testClass->createInstance('Italy.png');
        $instanceUri = $instance->getUri();

        $this->clearPropertyValues($instance, $linkProperty);
        $this->clearPropertyValues($instance, $mimeTypeProperty);

        $instance->setPropertyValue($linkProperty, 'MyLink');
        $instance->setPropertyValue($mimeTypeProperty, 'application/qti-xml');

        $mediaService = $this->initializeMockForEditInstance('MyLink');
        $mediaService->editMediaInstance($fileTmp, $instanceUri, $lang);

        $this->assertEquals(
            $lang,
            $instance->getUniquePropertyValue(
                new RdfProperty(TaoMediaOntology::PROPERTY_LANGUAGE)
            ),
            'The instance language is wrong'
        );

        $this->assertTrue($instance->delete(true));
    }

    private function clearPropertyValues(RdfResource $instance, RdfProperty $property): void
    {
        foreach ($instance->getPropertyValues($property) as $propertyValue) {
            $instance->removePropertyValue($property, $propertyValue);
        }
    }
}
