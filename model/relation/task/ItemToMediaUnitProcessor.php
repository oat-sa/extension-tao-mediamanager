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

namespace oat\taoMediaManager\model\relation\task;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\task\migration\service\ResultUnit;
use oat\tao\model\task\migration\service\ResultUnitProcessorInterface;
use oat\taoMediaManager\model\relation\service\IdDiscoverService;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;
use oat\taoQtiItem\model\qti\parser\ElementReferencesExtractor;
use oat\taoQtiItem\model\qti\Service;

class ItemToMediaUnitProcessor extends ConfigurableService implements ResultUnitProcessorInterface
{
    use OntologyAwareTrait;

    public function process(ResultUnit $unit): void
    {
        $id = $unit->getResource()['subject'];

        $resource = $this->getResource($id);

        $qtiItem = $this->getQtiService()->getDataItemByRdfItem($resource);

        $elementReferences = $this->getElementReferencesExtractor()
            ->extractAll($qtiItem)
            ->getAllReferences();

        if (!empty($elementReferences)) {
            $ids = $this->getIdDiscoverService()->discover($elementReferences);

            $this->getItemRelationUpdateService()
                ->updateByTargetId($unit->getUri(), $ids);
        }
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }

    private function getElementReferencesExtractor(): ElementReferencesExtractor
    {
        return $this->getServiceLocator()->get(ElementReferencesExtractor::class);
    }

    private function getQtiService(): Service
    {
        return $this->getServiceLocator()->get(Service::class);
    }

    private function getIdDiscoverService(): IdDiscoverService
    {
        return $this->getServiceLocator()->get(IdDiscoverService::class);
    }
}
