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

namespace oat\taoMediaManager\model\sharedStimulus\parser;

use LogicException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaException;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\MediaSource;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;
use tao_helpers_Uri;
use tao_models_classes_FileNotFoundException as FileNotFoundException;

/**
 * @todo As:
 * - this parser is taoMediaManager agnostic
 *     - excepts extractImageFileInfo
 *       - use for validation (atm)
 *       - should be part of another service
 * - taomedia://url is parsed from taoMediaResolver
 * then it can be moved to qti item
 * and use to create media relation from item
 * but need to include shared stimulus detection
 */
class SharedStimulusMediaParser extends ConfigurableService
{
    use OntologyAwareTrait;

    /** @var TaoMediaResolver */
    private $mediaResolver;

    /**
     * @throws TaoMediaException|LogicException
     */
    public function parse(string $xml): array
    {
        return $this->extractMedia(
            $xml,
            [$this, 'getMediaFileUri']
        );
    }

    /**
     * @throws FileNotFoundException|TaoMediaException
     */
    public function parseImageFileInfo(string $xml): array
    {
        return $this->extractMedia(
            $xml,
            [$this, 'extractImageFileInfo']
        );
    }

    public function withMediaResolver(TaoMediaResolver $resolver)
    {
        $this->mediaResolver = $resolver;

        return $this;
    }

    /**
     * @throws TaoMediaException
     */
    private function extractMedia(string $xml, callable $processImageIdentifier): array
    {
        try {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadFromString($xml, false);
        } catch (XmlStorageException $e) {
            throw new TaoMediaException('Shared stimulus XML cannot be processed.', 0, $e);
        }

        return array_merge(
            $this->processImages($xmlDocument, $processImageIdentifier),
            $this->processVideos($xmlDocument, $processImageIdentifier)
        );
    }

    private function processImages(XmlDocument $xmlDocument, callable $processImageIdentifier): array
    {
        $mediaImages = [];
        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');

        foreach ($images as $image) {
            $this->processMediaSource($image->getSrc(), $processImageIdentifier, $mediaImages);
        }

        return $mediaImages;
    }

    private function processVideos(XmlDocument $xmlDocument, callable $processImageIdentifier): array
    {
        $mediaVideos = [];
        $videos = $xmlDocument->getDocumentComponent()->getComponentsByClassName('object');

        foreach ($videos as $video) {
            $this->processMediaSource($video->getData(), $processImageIdentifier, $mediaVideos);
        }

        return $mediaVideos;
    }

    private function processMediaSource(string $uri, callable $processor, array &$matches): void
    {
        if (false === strpos($uri, 'data:image')) {
            $asset = $this->getMediaResolver()->resolve($uri);

            if ($asset->getMediaSource() instanceof MediaSource) {
                $matches[] = $processor($asset);
            }
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function extractImageFileInfo(MediaAsset $asset): array
    {
        return $asset->getMediaSource()->getFileInfo($asset->getMediaIdentifier());
    }

    /**
     * @throws InvalidMediaReferenceException
     */
    private function getMediaFileUri(MediaAsset $asset): string
    {
        $assetIdentifier = tao_helpers_Uri::decode($asset->getMediaIdentifier());

        if (!$this->getResource($assetIdentifier)->exists()) {
            throw new InvalidMediaReferenceException($assetIdentifier);
        }

        return $assetIdentifier;
    }

    private function getMediaResolver(): TaoMediaResolver
    {
        if (!$this->mediaResolver) {
            $this->mediaResolver = new TaoMediaResolver();
        }

        return $this->mediaResolver;
    }
}
