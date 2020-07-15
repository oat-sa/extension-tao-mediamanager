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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\relation\task;

use common_persistence_KeyValuePersistence;
use oat\generis\persistence\PersistenceManager;

trait PositionTrackTrait
{

    protected function keepCurrentPosition(int $position): void
    {
        $persistence = $this->getPositionStorage();
        $persistence->set(static::class . static::CACHE_KEY, $position);
    }

    protected function getPositionStorage(): common_persistence_KeyValuePersistence
    {
        return $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID)->getPersistenceById('default_kv');
    }

    protected function getLastPosition(string $taskClass): int
    {
        $cache = $this->getPositionStorage();
        $start = $cache->get($taskClass . AbstractRelationshipTask::CACHE_KEY);
        return $start ? (int)$start : 0;
    }

}
