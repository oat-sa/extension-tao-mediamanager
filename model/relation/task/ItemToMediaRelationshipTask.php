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

use core_kernel_classes_Resource;
use Iterator;
use oat\tao\model\TaoOntology;
use oat\taoQtiItem\model\qti\event\UpdatedItemEventDispatcher;
use oat\taoQtiItem\model\qti\Service;
use Throwable;

class ItemToMediaRelationshipTask extends AbstractRelationshipTask
{
    private function updateRelationship(core_kernel_classes_Resource $resource): void
    {
        $item = $this->getQtiService()->getDataItemByRdfItem($resource);
        if ($item) {
            $this->getUpdatedItemEventDispatcher()->dispatch($item, $resource);
        }
    }

    private function getUpdatedItemEventDispatcher(): UpdatedItemEventDispatcher
    {
        return $this->getServiceLocator()->get(UpdatedItemEventDispatcher::class);
    }

    private function getQtiService(): Service
    {
        return $this->getServiceLocator()->get(Service::class);
    }

    protected function getTargetClasses(): array
    {
        return $this->getClass(TaoOntology::CLASS_URI_ITEM)->getSubClasses(true);
    }

    protected function applyProcessor(Iterator $iterator): bool
    {
        /** @var array $item */
        $item = $iterator->current();
        ++$this->affected;

        $id = $item['id'];
        $subject = $item['subject'];

        try {
            echo sprintf(
                '%s %s %s %s',
                $this->affected,
                $id,
                $subject,
                PHP_EOL
            );

            $this->updateRelationship($this->getResource($subject));
        } catch (Throwable $exception) {
            $this->addAnomaly($id, $subject, $exception->getMessage());
        }

        $isOver = $this->pickSize ? $this->affected < $this->pickSize * 2 : true;

        return $isOver;
    }

}
