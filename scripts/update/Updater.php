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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts\update;

use common_Exception;
use common_exception_NotImplemented;
use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\media\MediaService;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoItems\model\event\ItemRemovedEvent;
use oat\taoItems\model\event\ItemUpdatedEvent;
use oat\taoMediaManager\model\relation\event\MediaRelationListener;
use oat\taoMediaManager\model\relation\event\MediaRemovedEvent;
use oat\taoMediaManager\model\relation\event\MediaSavedEvent;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;

class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * @param string $initialVersion
     * @return string|void
     * @throws common_exception_NotImplemented
     * @throws common_Exception
     */
    public function update($initialVersion)
    {
        if ($this->isBetween('0.0.0', '0.2.5')) {
            throw new common_exception_NotImplemented(
                'Updates from versions prior to Tao 3.1 are not longer supported, please update to Tao 3.1 first'
            );
        }

        $this->skip('0.3.0', '9.4.0');

        if ($this->isVersion('9.4.0')) {
            OntologyUpdater::syncModels();
            $this->getServiceManager()->register(
                MediaRelationRepositoryInterface::SERVICE_ID,
                new RdfMediaRelationRepository()
            );

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(ItemUpdatedEvent::class, [MediaRelationListener::class, 'whenItemIsUpdated']);
            $eventManager->attach(ItemRemovedEvent::class, [MediaRelationListener::class, 'whenItemIsRemoved']);
            $eventManager->attach(MediaRemovedEvent::class, [MediaRelationListener::class, 'whenMediaIsRemoved']);
            $eventManager->attach(MediaSavedEvent::class, [MediaRelationListener::class, 'whenMediaIsSaved']);

            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            /** @var FileSystemService $filesystemService */
            $filesystemService = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
            /** @var  $adapters */
            if ($filesystemService->hasDirectory('memory')) {
                $dirs = $filesystemService->getOption(FileSystemService::OPTION_DIRECTORIES);
                $dirs[CommandFactory::DEFAULT_DIRECTORY] = 'memory';
                $filesystemService->setOption(FileSystemService::OPTION_DIRECTORIES, $dirs);
            } else {
                $fileSystem = $filesystemService->createFileSystem(CommandFactory::DEFAULT_DIRECTORY);
            }

            $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $filesystemService);

            $this->setVersion('10.0.0');
        }

        $this->skip('10.0.0', '10.2.0');

        if ($this->isVersion('10.2.0')) {
            $originalMediaService = $this->getServiceManager()->get(MediaService::SERVICE_ID);
            $cleanedMediaService = new MediaService(
                [
                    MediaService::OPTION_SOURCE => $originalMediaService->getOption(MediaService::OPTION_SOURCE),
                ]
            );

            $this->getServiceManager()->register(MediaService::SERVICE_ID, $cleanedMediaService);

            $this->setVersion('11.0.0');
        }

        $this->skip('11.0.0', '11.0.3');
    }
}
