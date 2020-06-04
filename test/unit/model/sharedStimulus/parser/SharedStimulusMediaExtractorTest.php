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

use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\parser\InvalidMediaReferenceException;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use core_kernel_classes_Resource as RdfResource;
use Throwable;

class SharedStimulusMediaExtractorTest extends TestCase
{
    /** @var SharedStimulusMediaExtractor */
    private $subject;

    /** @var Ontology */
    private $ontology;

    /** @var TaoMediaResolver */
    private $resolver;

    public function setUp() :void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->resolver = $this->createMock(TaoMediaResolver::class);
        $this->subject = new SharedStimulusMediaExtractor();
        $this->subject->setServiceLocator($this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontology
        ]));
        $this->subject->withMediaResolver($this->resolver);
    }

    public function testExtractMediaIdentifiers(): void
    {
        $imagePath = 'taomedia://taomediamanager/image-path.png';
        $videoPath = 'taomedia://taomediamanager/video-path.png';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" >';
        $xml .= '<img src="' . $imagePath . '"/>';
        $xml .= '<object data="' . $videoPath . '" type="type" />';
        $xml .= '</div>';

        $this->loadResolver($imagePath, $videoPath);

        $resource = $this->createConfiguredMock(RdfResource::class, ['exists' => true]);

        $this->ontology
            ->expects($this->exactly(2))
            ->method('getResource')
            ->withConsecutive(
                [$this->equalTo($imagePath)],
                [$this->equalTo($videoPath)]
            )
            ->willReturn(
                $resource, $resource
            );

        $expected = [
            $imagePath,
            $videoPath,
        ];

        $this->assertSame(
            $expected,
            $this->subject->extractMediaIdentifiers($xml)
        );
    }

    public function testExtractNonExistingMediaIdentifiers(): void
    {
        $imagePath = 'http://image-path.png';
        $videoPath = 'taomedia://mediamanager/video-path.png';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" >';
        $xml .= '<img src="' . $imagePath . '"/>';
        $xml .= '<object data="' . $videoPath . '" type="type" />';
        $xml .= '</div>';

        $this->loadResolver($imagePath, $videoPath);

        $resource = $this->createConfiguredMock(RdfResource::class, ['exists' => false]);

        $this->ontology
            ->expects($this->once())
            ->method('getResource')
            ->with($this->equalTo($imagePath))
            ->willReturn($resource);

        $this->expectException(InvalidMediaReferenceException::class);

        $this->subject->extractMediaIdentifiers($xml);
    }

    public function testAssertMediaFileNotExists()
    {
        $imagePath = 'http://image-path.png';
        $videoPath = 'taomedia://mediamanager/video-path.png';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" >';
        $xml .= '<img src="' . $imagePath . '"/>';
        $xml .= '<object data="' . $videoPath . '" type="type" />';
        $xml .= '</div>';

        $mediaSource = $this->createMock(MediaSource::class);
        $mediaSource
            ->expects($this->once())
            ->method('getFileInfo')
            ->with($imagePath)
            ->willThrowException(new InvalidMediaReferenceException($imagePath));

        $imageAsset = $this->createConfiguredMock(MediaAsset::class, [
            'getMediaSource' => $mediaSource,
            'getMediaIdentifier' => $imagePath
        ]);

        $this->resolver
            ->method('resolve')
            ->with($imagePath)
            ->willReturn($imageAsset);

        $this->expectException(InvalidMediaReferenceException::class);

        $this->subject->assertMediaFileExists($xml);
    }

    public function testAssertMediaFileExists()
    {
        $imagePath = 'image-path.png';
        $videoPath = 'video-path.png';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" >';
        $xml .= '<img src="' . $imagePath . '"/>';
        $xml .= '<object data="' . $videoPath . '" type="type" />';
        $xml .= '</div>';

        $this->loadResolver($imagePath, $videoPath);

        try {
            $this->subject->assertMediaFileExists($xml);
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->fail('An exception has been thrown and should not if files are existing. ');
        }
    }

    private function loadResolver(string $imagePath, string $videoPath): void
    {
        $mediaSource = $this->createConfiguredMock(MediaSource::class, ['getFileInfo' => 'info']);
;
        $imageAsset = $this->createConfiguredMock(MediaAsset::class, [
            'getMediaSource' => $mediaSource,
            'getMediaIdentifier' => $imagePath
        ]);

        $videoAsset = $this->createConfiguredMock(MediaAsset::class, [
            'getMediaSource' => $mediaSource,
            'getMediaIdentifier' => $videoPath
        ]);

        $this->resolver
            ->method('resolve')
            ->withConsecutive([$this->equalTo($imagePath)], [$this->equalTo($videoPath)])
            ->willReturnOnConsecutiveCalls($imageAsset, $videoAsset);
    }
}
