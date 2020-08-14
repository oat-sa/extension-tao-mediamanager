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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\parser;

use oat\generis\test\TestCase;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\sourceStrategy\HttpSource;
use oat\tao\model\media\TaoMediaException;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaParser;

class SharedStimulusMediaParserTest extends TestCase
{
    /** @var SharedStimulusMediaParser */
    private $subject;

    /** @var TaoMediaResolver */
    private $resolver;

    public function setUp() :void
    {
        $this->resolver = $this->createMock(TaoMediaResolver::class);
        $this->subject = new SharedStimulusMediaParser();
        $this->subject->withMediaResolver($this->resolver);
    }

    public function testExtractMedia()
    {
        $imagePath = 'taomedia://taomediamanager/image-path.png';
        $imageDataUri = 'data:image/png;base64' . base64_encode('base64-image');
        $imageDataUri = 'data:video/mp4;base64' . base64_encode('base64-video');
        $objectPath = 'taomedia://taomediamanager/video-path.mp4';
        $objectHttp = 'http://video.tao';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" >';
        $xml .= '<img src="' . $imagePath . '"/>';
        $xml .= '<img src="' . $imageDataUri . '"/>';
        $xml .= '<object data="' . $objectPath . '" type="video" />';
        $xml .= '<object data="' . $objectHttp . '" type="video" />';
        $xml .= '</div>';

        $processor = function(MediaAsset $mediaAsset) {
            return 'processed::' . $mediaAsset->getMediaIdentifier();
        };

        $this->resolver
            ->expects($this->exactly(3))
            ->method('resolve')
            ->withConsecutive(
                [$this->equalTo($imagePath)],
                [$this->equalTo($objectPath)],
                [$this->equalTo($objectHttp)]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createMediaAsset($imagePath, MediaSource::class),
                $this->createMediaAsset($objectPath, MediaSource::class),
                $this->createMediaAsset($objectHttp, HttpSource::class)
            );

        $expected = [
            'processed::' . $imagePath,
            'processed::' . $objectPath,
        ];

        $this->assertSame(
            $expected,
            $this->subject->extractMedia($xml, $processor)
        );
    }

    public function testExtractMediaWithInvalidQtiXml()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><div><img src="fixture.tao"/></div>';

        $this->expectException(TaoMediaException::class);
        $this->subject->extractMedia($xml, function () {});
    }

    private function createMediaAsset(string $path, string $sourceClass): MediaAsset
    {
        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset
            ->expects($this->once())
            ->method('getMediaSource')
            ->willReturn(
                $this->createMock($sourceClass)
            );

        $mediaAsset
            ->expects($this->any())
            ->method('getMediaIdentifier')
            ->willReturn(
                $path
            );

        return $mediaAsset;
    }
}
