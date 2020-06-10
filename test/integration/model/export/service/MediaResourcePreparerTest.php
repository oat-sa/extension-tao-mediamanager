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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\integration\model\export\service;

use core_kernel_classes_Resource;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\MediaBrowser;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\export\service\MediaResourcePreparer;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use Psr\Http\Message\StreamInterface;

class MediaResourcePreparerTest extends TestCase
{
    private const REMOTE_IMAGE = 'taomedia://mediamanager/http_2_test_0_org_1_tao_0_rdf_3_i5ed8fb7fdec668fa42f651de12e1d1';
    private const REMOTE_IMAGE_CONTENTS = 'remote_image';
    private const REMOTE_IMAGE_BASE_64 = 'data:image/png;base64,cmVtb3RlX2ltYWdl';

    private const REMOTE_OBJECT = 'taomedia://mediamanager/http_2_test_0_org_1_tao_0_rdf_3_i5ed8fb7fdec668fa42f651de12e1d2';
    private const REMOTE_OBJECT_CONTENTS = 'remote_video';
    private const REMOTE_OBJECT_BASE_64 = 'data:video/quicktime;base64,cmVtb3RlX3ZpZGVv';

    private const IMAGE_BASE_64 = 'data:image/png;base64,cmVtb3RlX2ltYWdl';
    private const OBJECT_BASE_64 = 'data:video/quicktime;base64,cmVtb3RlX3ZpZGVv';

    private const WEB_IMAGE = 'https://test.com/image.png';
    private const WEB_OBJECT = 'https://test.com/video.mp4';

    /** @var MediaResourcePreparer */
    private $subject;

    /** @var TaoMediaResolver|MockObject */
    private $mediaResolver;

    /** @var FileManagement|MockObject */
    private $fileManagement;

    /** @var SharedStimulusResourceSpecification|MockObject */
    private $sharedStimulusResourceSpecification;

    public function setUp(): void
    {
        $this->mediaResolver = $this->createMock(TaoMediaResolver::class);
        $this->fileManagement = $this->createMock(FileManagement::class);
        $this->sharedStimulusResourceSpecification = $this->createMock(SharedStimulusResourceSpecification::class);
        $this->subject = new MediaResourcePreparer();
        $this->subject->withMediaResolver($this->mediaResolver);
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    FileManagement::SERVICE_ID => $this->fileManagement,
                    SharedStimulusResourceSpecification::class => $this->sharedStimulusResourceSpecification
                ]
            )
        );
    }

    public function testPrepare()
    {
        $this->sharedStimulusResourceSpecification
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $this->mediaResolver
            ->method('resolve')
            ->willReturnOnConsecutiveCalls(
                ...[
                    $this->createMediaAsset('img', 'img_link', 'image/png'),
                    $this->createUnsupportedMediaAsset(),
                    $this->createUnsupportedMediaAsset(),
                    $this->createMediaAsset('object', 'object_link', 'video/quicktime'),
                    $this->createUnsupportedMediaAsset(),
                    $this->createUnsupportedMediaAsset(),
                ]
            );

        $this->fileManagement
            ->method('getFileStream')
            ->willReturnOnConsecutiveCalls(
                ...[
                    $this->createStream(self::REMOTE_IMAGE_CONTENTS),
                    $this->createStream(self::REMOTE_OBJECT_CONTENTS),
                ]
            );

        $resource = $this->createMock(core_kernel_classes_Resource::class);

        $fileContent = sprintf(
            $this->getFileContent(),
            self::REMOTE_IMAGE,
            self::WEB_IMAGE,
            self::IMAGE_BASE_64,
            self::REMOTE_OBJECT,
            self::WEB_OBJECT,
            self::OBJECT_BASE_64
        );

        $expectedFileContent = sprintf(
            $this->getFileContent(),
            self::REMOTE_IMAGE_BASE_64,
            self::WEB_IMAGE,
            self::IMAGE_BASE_64,
            self::REMOTE_OBJECT_BASE_64,
            self::WEB_OBJECT,
            self::OBJECT_BASE_64
        );

        $this->assertXmlStringEqualsXmlString(
            $expectedFileContent,
            $this->subject->prepare($resource, $fileContent)
        );
    }

    private function createStream(string $contents): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn($contents);

        return $stream;
    }

    private function createMediaAsset(string $identifier, string $link, string $mime): MediaAsset
    {
        $mediaSource = $this->createMock(MediaSource::class);

        $mediaSource->method('getFileInfo')
            ->willReturn(
                [
                    'link' => $link,
                    'mime' => $mime,
                ]
            );

        $mediaAsset = $this->createMock(MediaAsset::class);

        $mediaAsset->method('getMediaSource')
            ->willReturn($mediaSource);

        $mediaAsset->method('getMediaIdentifier')
            ->willReturn($identifier);

        return $mediaAsset;
    }

    private function createUnsupportedMediaAsset(): MediaAsset
    {
        $mediaAsset = $this->createMock(MediaAsset::class);

        $mediaAsset->method('getMediaSource')
            ->willReturn($this->createMock(MediaBrowser::class));

        return $mediaAsset;
    }

    private function getFileContent(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
            <div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_v2p1.xsd">
                <img alt="test" src="%s"/>
                <img alt="test" src="%s"/>
                <img alt="test" src="%s"/>
                <object data="%s" type="video/quicktime"/>
                <object data="%s" type="video/quicktime"/>
                <object data="%s" type="video/quicktime"/>
            </div>';
    }
}
