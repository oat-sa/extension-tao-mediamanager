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

namespace oat\taoMediaManager\model\relation\repository\rdf\map;

use oat\generis\model\OntologyAwareTrait;
use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use core_kernel_classes_Resource as RdfResource;

class RdfMediaRelationMap implements RdfMediaRelationMapInterface
{
    private const MEDIA_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedMedia';

    use OntologyAwareTrait;

    /**
     * @inheritDoc
     */
    public function getMediaRelations(
        RdfResource $mediaResource,
        MediaRelationCollection $mediaRelationCollection
    ): void {
        $relatedAssets = $mediaResource->getPropertyValues($this->getProperty(self::MEDIA_RELATION_PROPERTY));

        foreach ($relatedAssets as $relatedAsset) {
            $assetResource = $this->getResource($relatedAsset);
            $mediaRelationCollection->add(
                new MediaRelation(MediaRelation::MEDIA_TYPE, $assetResource->getUri(), $assetResource->getLabel())
            );
        }
    }
}