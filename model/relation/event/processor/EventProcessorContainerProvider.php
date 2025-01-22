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

namespace oat\taoMediaManager\model\relation\event\processor;

use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\service\ItemMediaCollector;
use oat\taoMediaManager\model\relation\service\update\ItemRelationUpdateService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class EventProcessorContainerProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services->set(ItemDuplicationEventProcessor::class)
            ->args([
                service(MediaRelationRepositoryInterface::SERVICE_ID),
                service(ItemMediaCollector::class)
            ])
            ->public();

        $services->set(ResourceDeleteEventProcessor::class)
            ->args([
                service(ItemRelationUpdateService::class)
            ])
            ->public();

        $services->set(EventInstanceCopiedProcessor::class)
            ->args([
                service(MediaRelationRepositoryInterface::SERVICE_ID),
                service(ItemMediaCollector::class)
            ])
            ->public();
    }
}
