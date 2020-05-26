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

namespace oat\taoMediaManager\model\relation\event\processor;

use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\event\MediaRemovedEvent;
use oat\taoMediaManager\model\relation\service\update\MediaRelationUpdateService;

class MediaRemovedEventProcessor extends ConfigurableService implements EventProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function process(Event $event): void
    {
        if (!$event instanceof MediaRemovedEvent) {
            throw new InvalidEventException($event);
        }

        $this->getMediaRelationUpdateService()
            ->removeMediaRelations($event->getMediaId());
    }

    private function getMediaRelationUpdateService(): MediaRelationUpdateService
    {
        return $this->getServiceLocator()->get(MediaRelationUpdateService::class);
    }
}
