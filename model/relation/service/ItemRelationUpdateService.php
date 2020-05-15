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

use EasyRdf_Resource;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;

class ItemRelationUpdateService extends ConfigurableService
{
    use OntologyAwareTrait;

    public function updateByItem(string $itemId, array $currentMediaIds = []): void
    {
        $this->updateDifference('http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedItem', $itemId, $currentMediaIds);
    }

    public function updateByMedia(string $mediaId): void
    {
        $this->updateDifference('http://www.tao.lu/Ontologies/TAOMedia.rdf#RelatedMedia', $mediaId);
    }

    /**
     * @TODO Method will be migrated to repository after @camille's PR is approved / merged
     */
    private function updateDifference(string $sourceClass, string $sourceId, array $currentRelatedIds = []): void
    {
        $relatedMediaIds = $this->getRelatedMediaIds($sourceClass, $sourceId);

        $mediaIdsToInsert = array_diff($currentRelatedIds, $relatedMediaIds);
        $mediaIdsToRemove = array_diff($relatedMediaIds, $currentRelatedIds);

        foreach ($mediaIdsToRemove as $mediaId) {
            $mediaResource = $this->getModel()->getResource($mediaId);
            $mediaResource->removePropertyValue($this->getProperty($sourceClass), $sourceId);
        }

        foreach ($mediaIdsToInsert as $mediaId) {
            $mediaResource = $this->getModel()->getResource($mediaId);
            $mediaResource->setPropertyValue($this->getProperty($sourceClass), $sourceId);
        }
    }

    /**
     * @TODO Method will be migrated to repository after @camille's PR is approved / merged
     */
    private function getRelatedMediaIds(string $sourceClass, string $sourceId): array
    {
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Media', true);
        $query->add($sourceClass)->equals($sourceId);

        $queryBuilder->setCriteria($query);
        $result = $search->getGateway()->search($queryBuilder);

        $ids = [];

        /** @var EasyRdf_Resource $resource */
        foreach ($result as $resource) {
            $ids[] = $resource->getUri();
        }

        return $ids;
    }
}
