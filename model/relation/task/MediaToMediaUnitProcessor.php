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

use common_Exception;
use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Resource;
use Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\task\migration\service\ResultUnitProcessorInterface;
use oat\tao\model\task\migration\ResourceResultUnit;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\service\update\MediaRelationUpdateService;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;

class MediaToMediaUnitProcessor extends ConfigurableService implements ResultUnitProcessorInterface
{
    use OntologyAwareTrait;

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    public function process(ResourceResultUnit $unit): void
    {
        $resource = $unit->getResource();
        if (!($resource instanceof core_kernel_classes_Resource)){
            throw new Exception('Unit is not a resource');
        }

        if ($this->getSharedStimulusResourceSpecification()->isSatisfiedBy($resource)) {
            $fileLink = $resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_LINK));
            $fileLink = $fileLink instanceof core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
            $fileSource = $this->getFileManager()->getFileStream($fileLink);
            $content = $fileSource instanceof File ? $fileSource->read() : $fileSource->getContents();
            $elementIds = $this->getSharedStimulusExtractor()->extractMediaIdentifiers($content);
            $this->getMediaRelationUpdateService()->updateByTargetId($resource->getUri(), $elementIds);
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
