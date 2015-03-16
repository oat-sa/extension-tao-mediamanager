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


class MediaManagerManagementTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $mediaManagerManagement = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $service = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $fileManagerMock = null;

    private $classUri = null;

    private $returnedLink = null;

    public function setUp(){
        $this->classUri = 'http://myFancyDomaine.com/myGreatCLassUriToUploadTo';

        $this->service = $this->getMockBuilder('oat\taoMediaManager\model\MediaService')
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->returnedLink = 'myGreatLink';
        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array('oat\taoMediaManager\model\MediaService' => $this->service));


        $this->mediaManagerManagement = $this->getMockBuilder('oat\taoMediaManager\model\MediaManagerManagement')
            ->setMethods(array('getMediaBrowser'))
            ->setConstructorArgs(array(array('lang' => 'EN_en', 'rootClass' => $this->classUri)))
            ->getMock();

        //fileManagerMock
        $this->fileManagerMock = $this->getMockBuilder('oat\taoMediaManager\model\fileManagement\SimpleFileManagement')
            ->getMock();

        $ref = new \ReflectionProperty('oat\taoMediaManager\model\fileManagement\FileManager', 'fileManager');
        $ref->setAccessible(true);
        $ref->setValue(null, $this->fileManagerMock);

    }

    public function tearDown(){
        $this->fileManagerMock = null;

        $ref = new \ReflectionProperty('oat\taoMediaManager\model\fileManagement\FileManager', 'fileManager');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
        $ref->setAccessible(false);

        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array());

    }


    public function testUpload(){
        $classTao = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAO.rdf#TAOObject');
        $rootClass = $classTao->createSubClass('great', 'comment', $this->classUri);

        $filePath = dirname(__DIR__).'/sample/Italy.png';

        $this->service->expects($this->once())
            ->method('createMediaInstance')
            ->with(dirname($filePath).'/Italy1.png', $this->classUri, 'EN_en')
            ->willReturn($this->returnedLink);

        //mock the mediaBrowser fileInfo method
        $fileInfo = array(
            'name' => 'myName',
            'identifier' => 'mediamanager/',
            'relPath' => 'relativePath',
            'mime' => 'mime/type',
            'size' => 1024,
            'url' =>'myGreatUrl'
        );
        $mediaBrowserMock = $this->getMockBuilder('oat\taoMediaManager\model\MediaManagerBrowser')
                            ->setConstructorArgs(array(array('lang' => 'EN_en')))
                            ->getMock();

        $mediaBrowserMock->expects($this->once())
            ->method('getFileInfo')
            ->with($this->returnedLink, array())
            ->willReturn($fileInfo);

        $this->mediaManagerManagement->expects($this->any())
            ->method('getMediaBrowser')
            ->willReturn($mediaBrowserMock);

        $success = $this->mediaManagerManagement->add($filePath, 'Italy1.png', $this->classUri);

        // has no error
        $this->assertInternalType('array', $success, 'Should be a file info array');
        $this->assertArrayNotHasKey('error', $success, 'upload doesn\'t succeed');
        $this->assertEquals($fileInfo, $success, 'Doesn\'t return the getFileInfo value');

        $instance  = $rootClass->createInstance('Italy1.png');
        $instance->setPropertyValue(new \core_kernel_classes_Property(MEDIA_LINK), $this->returnedLink);

    }


    public function testUploadFail(){

        $filePath = dirname(__DIR__).'/sample/Unknown.png';

        $this->service->expects($this->never())
            ->method('createMediaInstance');

        $error = $this->mediaManagerManagement->add($filePath, 'Unknown.png', $this->classUri);

        $this->assertInternalType('array', $error, 'Should be an error array');
        $this->assertArrayHasKey('error', $error, 'upload succeed');
        $this->assertEquals('File '. $filePath .' not found', $error['error'], 'Doesn\'t return the right exception message');

    }

    /**
     * @depends testUpload
     */
    public function testDelete(){

        $this->fileManagerMock->expects($this->once())
            ->method('deleteFile')
            ->with($this->returnedLink)
            ->willReturn(true);

        $rootClass = new \core_kernel_classes_Class($this->classUri);
        $instances = $rootClass->searchInstances(array(MEDIA_LINK => $this->returnedLink), array('recursive' => true));
        $instance = array_pop($instances);
        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'This class should exists');

        $success = $this->mediaManagerManagement->delete($this->returnedLink);

        // should return true
        $this->assertTrue($success, 'The file is not deleted');

        // should remove the instance
        $removedInstance = new \core_kernel_classes_Class($instance->getUri());
        $this->assertEquals('',$instance->getLabel(), 'The instance still exists');
        $this->assertEquals('',$removedInstance->getLabel(), 'The instance still exists');

        //remove created class
        $rootClass->delete(true);
    }

    /**
     * @depends testDelete
     */
    public function testUploadFailNoClass(){

        $filePath = dirname(__DIR__).'/sample/Italy.png';

        $this->service->expects($this->never())
            ->method('createMediaInstance');

        $error = $this->mediaManagerManagement->add($filePath, 'Italy1.png', $this->classUri);

        $this->assertInternalType('array', $error, 'Should be an error array');
        $this->assertArrayHasKey('error', $error, 'upload succeed');
        $this->assertEquals('Class '. $this->classUri .' not found', $error['error'], 'Doesn\'t return the right exception message');

    }

}
 