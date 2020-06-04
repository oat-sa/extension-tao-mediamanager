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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\integration\model;

use oat\oatbox\service\ServiceManager;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaService;
use oat\generis\test\TestCase;
use core_kernel_classes_Resource as RdfResource;
use core_kernel_classes_Property as RdfProperty;

include_once dirname(__FILE__) . '/../../../includes/raw_start.php';

class MediaServiceTest extends TestCase
{
    /**
     * @var \core_kernel_classes_Class
     */
    private $testClass = null;

    public function setUp(): void
    {
        $this->testClass = (MediaService::singleton())->getRootClass()->createSubClass('test class');

        (MediaService::singleton())->deleteClass($this->testClass); //FIXME
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
        $thing = $instance->getUniquePropertyValue(new RdfProperty(MediaService::PROPERTY_LINK));

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
            $instance->getUniquePropertyValue(new RdfProperty(MediaService::PROPERTY_LANGUAGE)),
            'The instance language is wrong'
        );

        $this->assertTrue($instance->delete(true));
    }

    public function testEditMediaInstance()
    {
        $fileTmp = dirname(__DIR__) . '/sample/Italy.png';
        $lang = 'EN-en';

        $linkProperty = new RdfProperty(MediaService::PROPERTY_LINK);
        $mimeTypeProperty = new RdfProperty(MediaService::PROPERTY_MIME_TYPE);

        $instanceUri = 'http://myFancyDomain.com/myGreatInstanceUri';
        $instance = new RdfResource($instanceUri);

        $this->clearPropertyValues($instance, $linkProperty);
        $this->clearPropertyValues($instance, $mimeTypeProperty);

        $instance->setPropertyValue($linkProperty, 'MyLink');
        $instance->setPropertyValue($mimeTypeProperty, 'application/qti-xml');

        $mediaService = $this->initializeMockForEditInstance('MyLink');
        $mediaService->editMediaInstance($fileTmp, $instanceUri, $lang);

        $this->assertEquals(
            $lang,
            $instance->getUniquePropertyValue(new RdfProperty(MediaService::PROPERTY_LANGUAGE)),
            'The instance language is wrong'
        );

        $this->assertTrue($instance->delete());
    }

    private function clearPropertyValues(RdfResource $instance, RdfProperty $property): void
    {
        foreach ($instance->getPropertyValues($property) as $propertyValue) {
            $instance->removePropertyValue($property, $propertyValue);
        }
    }
}
