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
use oat\tao\model\task\migration\AbstractStatementMigrationTask;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\service\update\MediaRelationUpdateService;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
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

        if ($this->getSharedStimulusResourceSpecification()->isSatisfiedBy($resource)) {
            $fileLink = $resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_LINK));
            if (is_null($fileLink)) {
                throw new tao_models_classes_FileNotFoundException($uri);
            }
            $fileLink = $fileLink instanceof core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
            $fileSource = $this->getFileManager()->getFileStream($fileLink);
            $content = $fileSource instanceof File ? $fileSource->read() : $fileSource->getContents();
            $elementIds = $this->getSharedStimulusExtractor()->extractMediaIdentifiers($content);
            $this->getMediaRelationUpdateService()->updateByTargetId($uri, $elementIds);
        }
    }

    private function getSharedStimulusExtractor(): SharedStimulusMediaExtractor
    {
        return $this->getServiceLocator()->get(SharedStimulusMediaExtractor::class);
    }

    private function getFileManager(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }

    private function getMediaRelationUpdateService(): MediaRelationUpdateService
    {
        return $this->getServiceLocator()->get(MediaRelationUpdateService::class);
    }

    private function getSharedStimulusResourceSpecification(): SharedStimulusResourceSpecification
    {
        return $this->getServiceLocator()->get(SharedStimulusResourceSpecification::class);
    }
}
