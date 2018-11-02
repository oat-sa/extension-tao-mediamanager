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
use oat\taoMediaManager\model\MediaService;

include_once dirname(__FILE__) . '/../../../includes/raw_start.php';

class MediaServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \core_kernel_classes_Class
     */
    private $testClass = null;

    public function setUp()
    {
        $this->testClass = (MediaService::singleton())->getRootClass()->createSubClass('test class');
    }

    public function tearDown()
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
            ->setMethods(array('storeFile', 'deleteFile'))
            ->getMock();

        $fileManagerMock->expects($this->once())
            ->method('storeFile')
            ->with($fileTmp, basename($fileTmp))
            ->willReturn('MyGreatLink');

        $mediaService = MediaService::singleton();

        $ref = new \ReflectionProperty(MediaService::class, 'fileManager');
        $ref->setAccessible(true);
        $ref->setValue($mediaService, $fileManagerMock);

        return $mediaService;
    }

    private function initializeMockForEditInstance($fileTmp)
    {
        $fileManagerMock = $this->getMockBuilder(FlySystemManagement::class)
            ->setMethods(array('storeFile', 'deleteFile'))
            ->getMock();

        $fileManagerMock->expects($this->once())
            ->method('deleteFile')
            ->with($fileTmp)
            ->willReturn(true);

        $mediaService = MediaService::singleton();

        $ref = new \ReflectionProperty(MediaService::class, 'fileManager');
        $ref->setAccessible(true);
        $ref->setValue($mediaService, $fileManagerMock);

        return $mediaService;
    }

    public function testCreateMediaInstance()
    {
        $fileTmp = dirname(__DIR__) . '/sample/Brazil.png';
        $lang = 'EN-en';
        $classUri = $this->testClass->getUri();

        $mediaService = $this->initializeMockForCreateInstance($fileTmp);
        $uri = $mediaService->createMediaInstance($fileTmp, $classUri, $lang);

        $instance = new \core_kernel_classes_Resource($uri);
        $thing = $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK));
        $linkResult = $thing instanceof \core_kernel_classes_Resource ? $thing->getUri() : (string)$thing;
        $this->assertInstanceOf(
            '\core_kernel_classes_Resource',
            $instance,
            'It should create an instance under the class in parameter'
        );
        $this->assertEquals('Brazil.png', $instance->getLabel(), 'The instance label is wrong');
        $this->assertInternalType('string', $uri, 'The method return should be a string');
        $this->assertEquals($linkResult, 'MyGreatLink', 'The returned link is wrong');
        $this->assertEquals(
            $lang,
            $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LANGUAGE)),
            'The instance language is wrong'
        );

        $this->assertTrue($instance->delete(true));
    }

    public function testEditMediaInstance()
    {
        $fileTmp = dirname(__DIR__) . '/sample/Italy.png';
        $lang = 'EN-en';

        $instanceUri = 'http://myFancyDomain.com/myGreatInstanceUri';
        $instance = new \core_kernel_classes_Class($instanceUri);
        $instance->setPropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK), 'MyLink');

        $mediaService = $this->initializeMockForEditInstance('MyLink');
        $mediaService->editMediaInstance($fileTmp, $instanceUri, $lang);

        $this->assertEquals(
            $lang,
            $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LANGUAGE)),
            'The instance language is wrong'
        );

        // remove what has been done
        $inst = new \core_kernel_classes_Resource($instanceUri);
        $this->assertTrue($inst->delete());
    }
}
 