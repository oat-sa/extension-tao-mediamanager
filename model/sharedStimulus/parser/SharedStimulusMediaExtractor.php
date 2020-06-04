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

use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaException;
use tao_helpers_Uri;
use tao_models_classes_FileNotFoundException as FileNotFoundException;

class SharedStimulusMediaExtractor extends SharedStimulusMediaParser
{
    use OntologyAwareTrait;

    /**
     * @return string[]
     *
     * @throws TaoMediaException|InvalidMediaReferenceException
     */
    public function extractMediaIdentifiers(string $xml): array
    {
        return $this->extractMedia(
            $xml,
            [$this, 'getMediaFileUri']
        );
    }

    /**
     * @throws FileNotFoundException|TaoMediaException
     */
    public function assertMediaFileExists(string $xml): void
    {
        $this->extractMedia(
            $xml,
            [$this, 'extractImageFileInfo']
        );
    }

    /**
     * @throws InvalidMediaReferenceException
     */
    protected function extractImageFileInfo(MediaAsset $asset): void
    {
        $assetIdentifier = tao_helpers_Uri::decode($asset->getMediaIdentifier());

        try {
            $asset->getMediaSource()->getFileInfo($assetIdentifier);
        } catch (FileNotFoundException $exception) {
            throw new InvalidMediaReferenceException($assetIdentifier);
        }
    }

    /**
     * @throws InvalidMediaReferenceException
     */
    protected function getMediaFileUri(MediaAsset $asset): string
    {
        $assetIdentifier = tao_helpers_Uri::decode($asset->getMediaIdentifier());

        if (!$this->getResource($assetIdentifier)->exists()) {
            throw new InvalidMediaReferenceException($assetIdentifier);
        }

        return $assetIdentifier;
    }
}
