<?php

namespace oat\taoMediaManager\test\unit\model\export\service;

use core_kernel_classes_Resource;
use Laminas\ServiceManager\ServiceLocatorInterface;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\export\service\MediaReferencesNotFoundException;
use oat\taoMediaManager\model\export\service\MediaResourcePreparer;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use tao_models_classes_FileNotFoundException;

class MediaResourcePreparerTest extends TestCase
{
    private core_kernel_classes_Resource $mediaResourceMock;

    private StreamInterface $contentsMock;

    private ServiceLocatorInterface $serviceLocatorMock;

    private SharedStimulusResourceSpecification $sharedStimulusResSpecMock;

    private TaoMediaResolver $mediaResolverMock;

    private MediaAsset $mediaAssetMock;

    private MediaSource $mediaSourceMock;

    private MediaResourcePreparer $sut;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->mediaResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $this->contentsMock = $this->createMock(StreamInterface::class);
        $this->serviceLocatorMock = $this->createMock(ServiceLocatorInterface::class);
        $this->sharedStimulusResSpecMock = $this->createMock(SharedStimulusResourceSpecification::class);
        $this->mediaResolverMock = $this->createMock(TaoMediaResolver::class);
        $this->mediaAssetMock = $this->createMock(MediaAsset::class);
        $this->mediaSourceMock = $this->createMock(MediaSource::class);

        $this->sut = $this
            ->getMockBuilder(MediaResourcePreparer::class)
            ->onlyMethods(['getServiceLocator'])
            ->getMock();

        $this->sut = $this->sut->withMediaResolver($this->mediaResolverMock);
    }

    public function testPrepare(): void
    {
        $contents = file_get_contents(__DIR__ . '../../../../../resources/passage.xml');

        $this
            ->sut
            ->expects(self::exactly(1))
            ->method('getServiceLocator')
            ->willReturnOnConsecutiveCalls($this->serviceLocatorMock);

        $this
            ->serviceLocatorMock
            ->expects(self::exactly(1))
            ->method('get')
            ->willReturnOnConsecutiveCalls($this->sharedStimulusResSpecMock);

        $this
            ->sharedStimulusResSpecMock
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $this
            ->contentsMock
            ->expects(self::once())
            ->method('__toString')
            ->willReturn($contents);

        $this
            ->mediaResolverMock
            ->expects(self::once())
            ->method('resolve')
            ->willReturn($this->mediaAssetMock);

        $this
            ->mediaAssetMock
            ->expects(self::exactly(2))
            ->method('getMediaSource')
            ->willReturn($this->mediaSourceMock);

        $this
            ->mediaSourceMock
            ->expects(self::once())
            ->method('getFileInfo')
            ->willThrowException(
                new tao_models_classes_FileNotFoundException('puppy.jpg')
            );

        $this->expectException(MediaReferencesNotFoundException::class);
        $this->expectExceptionMessage(
            'Media references to Image: puppy.jpg FilePath: puppy.jpg could not be found.'
        );

        $this->sut->prepare($this->mediaResourceMock, $this->contentsMock);
    }
}
