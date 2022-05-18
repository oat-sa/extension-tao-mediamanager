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

use oat\tao\model\resources\Contract\InstanceContentCopierInterface;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use Psr\Log\LoggerInterface;

class AssetInstanceContentCopier implements InstanceContentCopierInterface
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

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function copy(
        core_kernel_classes_Resource $instance,
        core_kernel_classes_Resource $destinationInstance
    ): void {
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_ALT_TEXT);
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_LANGUAGE);
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_MD5);
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_MIME);

        // References the original file instead of making a copy of it
        $this->copyProperty($instance, $destinationInstance, self::PROPERTY_LINK);
    }

    private function copyProperty(
        core_kernel_classes_Resource $source,
        core_kernel_classes_Resource $destination,
        string $propertyId
    ): void {
        $property = $source->getProperty($propertyId);
        $this->debug(
            "Property %s (URI: %s, LanguageDependent: %s)",
            $property->getLabel(),
            $property->getUri(),
            $property->isLgDependent() ? 'y' : 'n'
        );

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
            $this->debug(
                "NL Setting property %s value to %s for instance %s",
                $property->getUri(),
                $this->formatPropertyValue($value),
                $destination->getUri()
            );

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

            assert(is_string($lang) && is_scalar($lang)); // @todo Not needed anymore

            $this->debug("Property %s lang=%s", $property->getUri(), $lang);

            $values = $source->getPropertyValuesCollection(
                $property,
                [
                    'lg' => $lang
                ]
            );

            foreach ($values as $value) {
                $this->debug(
                    "Setting property %s = %s for instance %s (type=%s %s)",
                    $property->getUri(),
                    $this->formatPropertyValue($value),
                    $destination->getUri(),
                    gettype($value),
                    (is_object($value) ? get_class($value) : '')
                );

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

    // @todo To be deleted before merge
    private function debug(string $format, string ...$va_args): void
    {
        $this->logger->info(__CLASS__ . ' MM ' . vsprintf($format, $va_args));
    }
}
