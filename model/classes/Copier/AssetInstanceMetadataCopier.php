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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

namespace oat\taoMediaManager\model\classes\Copier;

use oat\tao\model\resources\Contract\InstanceMetadataCopierInterface;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;

class AssetInstanceMetadataCopier implements InstanceMetadataCopierInterface
{
    /**
     * Base filename for the asset (i.e. 123456789abcdef123456.mp4)
     */
    private const PROPERTY_LINK = TaoMediaOntology::PROPERTY_LINK;

    /**
     * MD5 hash of the file contents
     */
    private const PROPERTY_MD5 = TaoMediaOntology::PROPERTY_MD5;

    private const PROPERTY_ALT_TEXT = TaoMediaOntology::PROPERTY_ALT_TEXT;

    private const PROPERTY_LANGUAGE = TaoMediaOntology::PROPERTY_LANGUAGE;

    private const PROPERTY_MIME = TaoMediaOntology::PROPERTY_MIME_TYPE;

    /** @var InstanceMetadataCopierInterface */
    private $nestedCopier;

    public function __construct(
        InstanceMetadataCopierInterface $nestedCopier
    ) {
        $this->nestedCopier = $nestedCopier;
    }
    public function copy(
        core_kernel_classes_Resource $instance,
        core_kernel_classes_Resource $destinationInstance
    ): void {
        $this->nestedCopier->copy($instance, $destinationInstance);

        // Doesn't seem to be copied by the wrapped class
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_ALT_TEXT);
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_LANGUAGE);
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_MD5);
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_MIME);

        // References the original file instead of creating a copy
        //
        $this->copyProperty(
            $instance,
            $destinationInstance,
            self::PROPERTY_LINK
        );
    }

    private function copyProperty(
        core_kernel_classes_Resource $source,
        core_kernel_classes_Resource $destination,
        string $propertyId
    ): void {
        $property = $source->getProperty($propertyId);

        if ($property->isLgDependent()) {
            $this->copyLanguageDependentProperty($source, $destination, $property);
            return;
        }

        $this->copyNonLanguageDependentProperty($source, $destination, $property);
    }

    private function copyNonLanguageDependentProperty(
        core_kernel_classes_Resource $source,
        core_kernel_classes_Resource $destination,
        core_kernel_classes_Property $property
    ): void {
        $values = $source->getPropertyValuesCollection($property);

        foreach ($values as $value) {
            $destination->setPropertyValue(
                $property,
                $this->formatPropertyValue($value)
            );
        }
    }

    private function copyLanguageDependentProperty(
        core_kernel_classes_Resource $source,
        core_kernel_classes_Resource $destination,
        core_kernel_classes_Property $property
    ): void {
        foreach ($source->getUsedLanguages($property) as $lang) {
            /** @var $lang string */

            $values = $source->getPropertyValuesCollection(
                $property,
                [
                    'lg' => $lang
                ]
            );

            foreach ($values as $value) {
                $destination->setPropertyValueByLg(
                    $property,
                    $this->formatPropertyValue($value),
                    $lang
                );
            }
        }
    }

    private function formatPropertyValue($value): string
    {
        if ($value instanceof core_kernel_classes_Resource) {
            return $value->getUri();
        }

        return (string) $value;
    }
}
