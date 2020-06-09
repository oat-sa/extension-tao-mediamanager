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

namespace oat\taoMediaManager\model\sharedStimulus\specification;

use common_Exception;
use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Literal;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\MediaService;

class SharedStimulusResourceSpecification extends ConfigurableService
{
    use OntologyAwareTrait;

    /**
     * @throws common_Exception
     */
    public function isSatisfiedBy(core_kernel_classes_Resource $resource): bool
    {
        try {
            $propertyValue = $resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_MIME_TYPE));

            if ($propertyValue instanceof core_kernel_classes_Literal) {
                return $propertyValue->literal === MediaService::SHARED_STIMULUS_MIME_TYPE;
            }
        } catch (core_kernel_classes_EmptyProperty $exception) {
        }

        return false;
    }
}
