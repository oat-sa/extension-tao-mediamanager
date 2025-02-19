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

namespace oat\taoMediaManager\scripts;

use common_ext_action_InstallAction;
use oat\generis\model\data\Ontology;
use oat\tao\model\menu\Action;

/**
 * Remove broken resource relation map
 */
class RemoveBrokenResourceRelationMap extends common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        $mediaRoot = $this->getOntology()->getClass('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');
        $nestedResources = $mediaRoot->getNestedResources();
        $nestedResources = array_filter($nestedResources, function ($nestedResource) {
            return $nestedResource['isclass'] === 0;
        });
        foreach ($nestedResources as $nestedResource) {
            $resource = $this->getOntology()->getResource($nestedResource['id']);
            $property = $this->getOntology()->getProperty('http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem');
            foreach ($resource->getPropertyValues($property) as $relationResource) {
                $relationResource = $this->getOntology()->getResource($relationResource);
                if (!$relationResource->exists()) {
                    $resource->removePropertyValue($property, $relationResource->getUri());
                }
            }
        }
    }

    private function getOntology(): Ontology
    {
        return $this->getServiceManager()->get(Ontology::SERVICE_ID);
    }
}
