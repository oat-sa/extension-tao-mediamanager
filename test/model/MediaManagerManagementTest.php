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


class MediaManagerManagementTest extends \PHPUnit_Framework_TestCase
{

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

    public function setUp()
    {
        $this->classUri = 'http://myFancyDomaine.com/myGreatCLassUriToUploadTo';

        $this->service = $this->getMockBuilder('oat\taoMediaManager\model\MediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array('oat\taoMediaManager\model\MediaService' => $this->service));


        $this->mediaManagerManagement = $this->getMockBuilder('oat\taoMediaManager\model\MediaManagerManagement')
            ->setMethods(array('getFileInfo'))
            ->setConstructorArgs(array(array('lang' => 'EN_en', 'rootClass' => $this->classUri)))
            ->getMock();

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

        $ref = new \ReflectionProperty('tao_models_classes_Service', 'instances');
        $ref->setAccessible(true);
        $ref->setValue(null, array());

    }


    public function testAdd()
    {
        $classTao = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAO.rdf#TAOObject');
        $rootClass = $classTao->createSubClass('great', 'comment', $this->classUri);

        $filePath = dirname(__DIR__) . '/sample/Italy.png';

        $instance = $rootClass->createInstance();
        $instance->setPropertyValue(new \core_kernel_classes_Property(MEDIA_LINK), 'myGreatLink');
        $returnedLink = $instance->getUri();
        $this->service->expects($this->once())
            ->method('createMediaInstance')
            ->with($filePath, $this->classUri, 'EN_en', 'Italy1.png')
            ->willReturn($returnedLink);

        //mock the fileInfo method
        $fileInfo = array(
            'name' => 'myName',
            'mime' => 'mime/type',
            'size' => 1024,
        );

        $this->mediaManagerManagement->expects($this->once())
            ->method('getFileInfo')
            ->with($returnedLink)
            ->willReturn($fileInfo);


        $success = $this->mediaManagerManagement->add($filePath, 'Italy1.png', $this->classUri);

        // has no error
        $this->assertInternalType('array', $success, 'Should be a file info array');
        $this->assertArrayNotHasKey('error', $success, 'upload doesn\'t succeed');
        $this->assertEquals($fileInfo, $success, 'Doesn\'t return the getFileInfo value');
        return $returnedLink;
    }


    /**
     * @expectedException \tao_models_classes_FileNotFoundException
     * @expectedExceptionMessageRegExp /File [^\s]+ not found/
     */
    public function testUploadFail()
    {

        $filePath = dirname(__DIR__) . '/sample/Unknown.png';

        $this->service->expects($this->never())
            ->method('createMediaInstance');

        $this->mediaManagerManagement->add($filePath, 'Unknown.png', $this->classUri);

    }

    /**
     * @depends testAdd
     */
    public function testDelete($returnedLink)
    {

        $this->fileManagerMock->expects($this->once())
            ->method('deleteFile')
            ->with('myGreatLink')
            ->willReturn(true);

        $instance = new \core_kernel_classes_Resource($returnedLink);
        $this->assertInstanceOf('\core_kernel_classes_Resource', $instance, 'This class should exists');

        $success = $this->mediaManagerManagement->delete($returnedLink);

        // should return true
        $this->assertTrue($success, 'The file is not deleted');

        // should remove the instance
        $removedInstance = new \core_kernel_classes_Class($instance->getUri());
        $this->assertFalse($instance->exists(), 'The instance still exists');
        $this->assertFalse($removedInstance->exists(), 'The instance still exists');

        //remove created class
        $rootClass = new \core_kernel_classes_Class($this->classUri);
        $rootClass->delete(true);
    }

    /**
     * @depends testDelete
     * @expectedException \common_exception_Error
     * @expectedExceptionMessageRegExp /Class [^\s]+ not found/
     */
    public function testUploadFailNoClass()
    {

        $filePath = dirname(__DIR__) . '/sample/Italy.png';

        $this->service->expects($this->never())
            ->method('createMediaInstance');

        $this->mediaManagerManagement->add($filePath, 'Italy1.png', $this->classUri);

    }

}
 