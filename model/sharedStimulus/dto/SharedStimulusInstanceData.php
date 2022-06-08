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

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\dto;

use oat\generis\model\OntologyRdfs;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;

class SharedStimulusInstanceData
{
    /** @var string */
    public $resourceUri;

    /** @var ?string */
    public $label;

    /** @var ?string */
    public $link;

    /** @var ?string */
    public $language;

    /** @var ?string */
    public $md5;

    /** @var ?string */
    public $mimeType;

    /** @var ?string */
    public $altText;

    public function __construct(
        string $resourceUri,
        ?string $label,
        ?string $link,
        ?string $language,
        ?string $md5,
        ?string $mimeType,
        ?string $altText
    ) {
        $this->resourceUri = $resourceUri;
        $this->label       = $label;
        $this->link        = $link;
        $this->language    = $language;
        $this->md5         = $md5;
        $this->mimeType    = $mimeType;
        $this->altText     = $altText;
    }

    public static function fromResource(
        core_kernel_classes_Resource $resource,
        string $dataLanguage
    ): self {
        return new SharedStimulusInstanceData(
            $resource->getUri(),
            self::getOneProperty($resource, OntologyRdfs::RDFS_LABEL, $dataLanguage),
            self::getOneProperty($resource, TaoMediaOntology::PROPERTY_LINK, $dataLanguage),
            self::getOneProperty($resource, TaoMediaOntology::PROPERTY_LANGUAGE, $dataLanguage),
            self::getOneProperty($resource, TaoMediaOntology::PROPERTY_MD5, $dataLanguage),
            self::getOneProperty($resource, TaoMediaOntology::PROPERTY_MIME_TYPE, $dataLanguage),
            self::getOneProperty($resource, TaoMediaOntology::PROPERTY_ALT_TEXT, $dataLanguage)
        );
    }

    private static function getOneProperty(
        core_kernel_classes_Resource $resource,
        string $propertyUri,
        string $dataLanguage
    ): ?string {
        $value = $resource->getPropertyValuesByLg(
            new core_kernel_classes_Property($propertyUri),
            $dataLanguage
        );

        if ($value->isEmpty()) {
            return null;
        }

        return (string) $value->get(0);
    }
}
