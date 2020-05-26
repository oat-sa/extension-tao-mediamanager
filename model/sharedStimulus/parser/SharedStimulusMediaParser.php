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

use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaException;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\MediaSource;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;
use tao_helpers_Uri;
use tao_models_classes_FileNotFoundException as FileNotFoundException;

class SharedStimulusMediaParser extends ConfigurableService
{
    /**
     * @throws TaoMediaException
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

        $matches = [];

        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');
        foreach ($images as $image) {
            $source = $image->getSrc();
            if (false !== strpos($source, 'data:image')) {
                continue;
            }
            $this->processMediaSource($source, $processImageIdentifier, $matches);
        }

        $videos = $xmlDocument->getDocumentComponent()->getComponentsByClassName('object');
        foreach ($videos as $video) {
            $source = $video->getData();
            $this->processMediaSource($source, $processImageIdentifier, $matches);
        }

        return $matches;
    }

    private function processMediaSource(string $uri, callable $processor, array &$matches): void
    {
        $asset = $this->getMediaResolver()->resolve($uri);
        if ($asset->getMediaSource() instanceof MediaSource) {
            $matches[] = $processor($asset);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function extractImageFileInfo(MediaAsset $asset)
    {
        return $asset->getMediaSource()->getFileInfo($asset->getMediaIdentifier());
    }

    private function getMediaFileUri(MediaAsset $asset): string
    {
        return tao_helpers_Uri::decode($asset->getMediaIdentifier());
    }

    private function getMediaResolver(): TaoMediaResolver
    {
        return new TaoMediaResolver();
    }
}