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

namespace oat\taoMediaManager\model\relation\task;

use core_kernel_classes_Resource;
use oat\oatbox\filesystem\File;
use oat\tao\model\task\AbstractStatementMigrationTask;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\event\MediaSavedEvent;
use oat\taoMediaManager\model\relation\event\processor\MediaSavedEventProcessor;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use tao_models_classes_FileNotFoundException;

class MediaToMediaRelationMigrationTask extends AbstractStatementMigrationTask
{

    protected function getTargetClasses(): array
    {
        return array_merge(
            [MediaService::ROOT_CLASS_URI],
            array_keys($this->getClass(MediaService::ROOT_CLASS_URI)->getSubClasses(true))
        );
    }

    protected function processUnit(array $unit): void
    {
        $uri = $unit['subject'];
        $resource = $this->getResource($uri);

        $fileLink = $resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_LINK));
        if (is_null($fileLink)) {
            throw new tao_models_classes_FileNotFoundException($uri);
        }
        $fileLink = $fileLink instanceof core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        $fileSource = $this->getFileManager()->getFileStream($fileLink);

        $mimeType = (string)$resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_MIME_TYPE));

        if ($this->isSharedStimulus($mimeType)) {
            $content = $fileSource instanceof File ? $fileSource->read() : $fileSource->getContents();
            $elementIds = $this->getSharedStimulusExtractor()->extractMediaIdentifiers($content);
            $this->getMediaProcessor()->process(new MediaSavedEvent($uri, $elementIds));
        }
    }

    private function getSharedStimulusExtractor(): SharedStimulusMediaExtractor
    {
        return $this->getServiceLocator()->get(SharedStimulusMediaExtractor::class);
    }

    private function isSharedStimulus(string $mimeType): bool
    {
        return $mimeType === MediaService::SHARED_STIMULUS_MIME_TYPE;
    }

    private function getFileManager(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }

    private function getMediaProcessor(): MediaSavedEventProcessor
    {
        return $this->getServiceLocator()->get(MediaSavedEventProcessor::class);
    }
}
