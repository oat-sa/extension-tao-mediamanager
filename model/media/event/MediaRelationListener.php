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

namespace oat\taoMediaManager\model\media\event;

use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoItems\model\event\ItemRemovedEvent;
use oat\taoItems\model\event\ItemUpdatedEvent;

class MediaRelationListener extends ConfigurableService
{
    use LoggerAwareTrait;

    public function whenItemIsUpdated(ItemUpdatedEvent $event): void
    {
        $this->logInfo('Item was updated. Checking shared stimulus relation...');
    }

    public function whenItemIsRemoved(ItemRemovedEvent $event): void
    {
        $this->logInfo('Item was removed. Checking shared stimulus relation...');
    }
}
