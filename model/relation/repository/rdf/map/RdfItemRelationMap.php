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

use oat\taoMediaManager\model\relation\MediaRelation;
use core_kernel_classes_Resource as RdfResource;

class RdfItemRelationMap extends AbstractRdfMediaRelationMap
{
    /** @var string */
    public const ITEM_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem';

    protected function getMediaRelationPropertyUri(): string
    {
        return self::ITEM_RELATION_PROPERTY;
    }

    public function createMediaRelation(RdfResource $mediaResource, string $sourceId): MediaRelation
    {
        return (new MediaRelation(MediaRelation::ITEM_TYPE, $mediaResource->getUri(), $mediaResource->getLabel()))
            ->withSourceId($sourceId);
    }
}
