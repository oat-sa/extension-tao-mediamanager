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
use oat\taoMediaManager\model\relation\event\processor\ItemRemovedEventProcessor;
use oat\taoMediaManager\model\relation\event\processor\ItemUpdatedEventProcessor;
use oat\taoMediaManager\model\relation\event\processor\MediaRemovedEventProcessor;
use oat\taoMediaManager\model\relation\event\processor\EventProcessorInterface;
use Throwable;

class MediaRelationListener extends ConfigurableService
{
    use LoggerAwareTrait;

    public function whenItemIsUpdated(Event $event): void
    {
        $this->process(ItemUpdatedEventProcessor::class, $event);
    }

    public function whenItemIsRemoved(Event $event): void
    {
        $this->process(ItemRemovedEventProcessor::class, $event);
    }

    public function whenMediaIsRemoved(Event $event): void
    {
        $this->process(MediaRemovedEventProcessor::class, $event);
    }

    public function whenMediaIsSaved(Event $event): void
    {
        //@TODO will be used to related shared stimulus with other media
    }

    private function process(string $processor, Event $event): void
    {
        try {
            $this->logDebug(sprintf('Processing event %s', get_class($event)));

            /** @var EventProcessorInterface $processor */
            $processor = $this->getServiceLocator()->get($processor);
            $processor->process($event);

            $this->logDebug(sprintf('Event %s processed', get_class($event)));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error processing event %s: %s', get_class($event), $exception->getMessage()));
        }
    }
}
