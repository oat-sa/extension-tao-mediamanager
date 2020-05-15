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

namespace oat\taoMediaManager\model\relation\service;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;

class ItemRelationUpdateService extends ConfigurableService
{
    use OntologyAwareTrait;

    public function update(string $itemId, array $currentMediaIds = []): array
    {
        $relatedMediaIds = $this->getRelatedMediaIds($itemId);

        $mediaIdsToInsert = array_diff($currentMediaIds, $relatedMediaIds);
        $mediaIdsToRemove = array_diff($relatedMediaIds, $currentMediaIds);

        exit(); //@TODO Testing - WIP
    }

    /**
     * @TODO Method will be migrated to repository after @camille's PR is approved / merged
     */
    private function getRelatedMediaIds(string $itemId): array
    {
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Media', true);
        $query->add('http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem')->equals($itemId);

        $queryBuilder->setCriteria($query);
        $result = $search->getGateway()->search($queryBuilder);

        $ids = [];

        /** @var \EasyRdf_Resource $media */
        foreach ($result as $media) {
            $ids[] = $media->getUri();
        }

        return $ids;
    }
}
