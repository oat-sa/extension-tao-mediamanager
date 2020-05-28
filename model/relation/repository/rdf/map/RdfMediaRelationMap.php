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

class RdfMediaRelationMap extends AbstractRdfMediaRelationMap
{
    /** @var string */
    private const MEDIA_RELATION_PROPERTY = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedMedia';

    public function getTargetType(): string
    {
        return MediaRelation::MEDIA_TYPE;
    }

    public function getMediaRelationPropertyUri(): string
    {
        return self::MEDIA_RELATION_PROPERTY;
    }
}
