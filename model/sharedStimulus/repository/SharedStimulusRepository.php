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

namespace oat\taoMediaManager\model\sharedStimulus\repository;

use common_Exception;
use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Literal;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\FindQuery;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;

class SharedStimulusRepository extends ConfigurableService implements SharedStimulusRepositoryInterface
{
    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    public function find(FindQuery $query): SharedStimulus
    {
        $resource = $this->getOntology()->getResource($query->getId());

        return new SharedStimulus(
            $query->getId(),
            $this->getPropertyValue($resource, MediaService::PROPERTY_ALT_TEXT),
            $this->getPropertyValue($resource, MediaService::PROPERTY_LANGUAGE),
            $this->getContent($resource)
        );
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    private function getContent(core_kernel_classes_Resource $resource): string
    {
        $link = $this->getPropertyValue($resource, MediaService::PROPERTY_LINK);

        if (is_string($link)) {
            $link = $this->getMediaSource()->unserializeAndRemovePrefixForAssets($link);
        }

        return (string)$this->getFileManagement()->getFileStream($link);
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    private function getPropertyValue(core_kernel_classes_Resource $resource, string $uri)
    {
        $propertyValue = $resource->getUniquePropertyValue($resource->getProperty($uri));

        if ($propertyValue instanceof core_kernel_classes_Resource) {
            return $propertyValue->getUri();
        }

        if ($propertyValue instanceof core_kernel_classes_Literal) {
            return (string)$propertyValue;
        }
    }

    private function getFileManagement(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }

    private function getOntology(): Ontology
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID);
    }

    private function getMediaSource(): MediaSource
    {
        return $this->getServiceLocator()->get(MediaSource::class);
    }
}
