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

namespace oat\taoMediaManager\model\relation\event;

use oat\oatbox\event\Event;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\service\ItemRelationUpdateService;

class MediaRelationListener extends ConfigurableService
{
    use LoggerAwareTrait;

    public function whenItemIsUpdated(Event $event): void
    {
        $this->logInfo('Item ' . $event->getItemUri() . ' with data ' . var_export($event->getData(), true) . ' was updated. Checking shared stimulus relation...');

        $itemId = $event->getItemUri();
        $currentMediaIds = (array)($event->getData()['includedElementIds'] ?? []);

        $this->getItemRelationUpdateService()->updateByItem($itemId, $currentMediaIds);

        $this->logInfo('OK 1 !!!');
    }

    public function whenItemIsRemoved(Event $event): void
    {
        $this->logInfo('Item ' . var_export($event->jsonSerialize(), true) . ' was removed. Checking shared stimulus relation...');

        $this->getItemRelationUpdateService()->updateByItem($event->jsonSerialize()['itemUri']);

        $this->logInfo('OK 2 !!!');
    }

    public function whenMediaIsRemoved(MediaRemovedEvent $event): void
    {
        $this->logInfo('Media ' . $event->getMediaId() . ' was removed. Checking shared stimulus relation...');

        //FIXME $this->getItemRelationUpdateService()->updateByMedia($event->getMediaId());

        $this->logInfo('OK 3 !!!');

    }

    public function whenMediaIsSaved(MediaSavedEvent $event): void
    {
        // @TODO will be used to related shared stimulus with other media
    }

    private function getItemRelationUpdateService(): ItemRelationUpdateService
    {
        return $this->getServiceLocator()->get(ItemRelationUpdateService::class);
    }
}
