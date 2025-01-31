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
 * Copyright (c) 2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\relation\service;

use common_Exception;
use oat\generis\model\data\Ontology;
use oat\taoQtiItem\model\qti\Element;
use oat\taoQtiItem\model\qti\Service as ItemsService;
use tao_helpers_Uri;

class ItemMediaCollector
{
    private Ontology $ontology;
    private ItemsService $itemsService;
    public function __construct(Ontology $ontology, ItemsService $itemsService)
    {
        $this->ontology = $ontology;
        $this->itemsService = $itemsService;
    }
    public function getItemMediaResources(string $itemUri): array
    {
        $mediaUris = [];
        $itemResource = $this->ontology->getResource($itemUri);
        foreach ($this->getImgElements($itemResource) as $element) {
            $mediaUris[] = tao_helpers_Uri::decode(str_replace('taomedia://mediamanager/', '', $element->getAttributeValue('src')));
        }

        return $mediaUris;
    }

    /**
     * @return Element[]
     * @throws common_Exception
     */
    private function getImgElements(\core_kernel_classes_Resource $itemResource): array
    {
        return $this->itemsService
            ->getDataItemByRdfItem($itemResource)
            ->getBody()
            ->getComposingElements('oat\taoQtiItem\model\qti\Img');
    }
}
