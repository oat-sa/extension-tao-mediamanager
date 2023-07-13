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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoMediaManager\scripts\install;

use common_report_Report;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\media\MediaService;
use oat\tao\model\resources\relation\service\ResourceRelationServiceInterface;
use oat\tao\model\resources\relation\service\ResourceRelationServiceProxy;
use oat\taoItems\model\event\ItemRemovedEvent;
use oat\taoItems\model\event\ItemUpdatedEvent;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\relation\event\MediaRelationListener;
use oat\taoMediaManager\model\relation\event\MediaRemovedEvent;
use oat\taoMediaManager\model\relation\event\MediaSavedEvent;
use oat\taoMediaManager\model\relation\MediaRelationService;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;

class SetMediaManager extends InstallAction
{
    public function __invoke($params)
    {
        $fsService = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
        $fsService->createFileSystem('mediaManager');

        $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $fsService);
        $this->getServiceManager()->register(
            FileManagement::SERVICE_ID,
            new FlySystemManagement(
                [
                    FlySystemManagement::OPTION_FS => 'mediaManager'
                ]
            )
        );
        $this->getServiceManager()
            ->register(MediaRelationRepositoryInterface::SERVICE_ID, new RdfMediaRelationRepository());

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(ItemUpdatedEvent::class, [MediaRelationListener::class, 'whenItemIsUpdated']);
        $eventManager->attach(ItemRemovedEvent::class, [MediaRelationListener::class, 'whenItemIsRemoved']);
        $eventManager->attach(MediaRemovedEvent::class, [MediaRelationListener::class, 'whenMediaIsRemoved']);
        $eventManager->attach(MediaSavedEvent::class, [MediaRelationListener::class, 'whenMediaIsSaved']);

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        /** @var MediaService $mediaService */
        $mediaService = $this->getServiceManager()->get(MediaService::SERVICE_ID);
        $mediaService->addMediaSource(new MediaSource());

        if ($fsService->hasDirectory('memory')) {
            $dirs = $fsService->getOption(FileSystemService::OPTION_DIRECTORIES);
            $dirs[CommandFactory::DEFAULT_DIRECTORY] = 'memory';
            $fsService->setOption(FileSystemService::OPTION_DIRECTORIES, $dirs);
        } else {
            $fileSystem = $fsService->createFileSystem(CommandFactory::DEFAULT_DIRECTORY);
        }

        $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $fsService);

        /** @var ResourceRelationServiceInterface $resourceRelationService */
        $resourceRelationService = $this->getServiceManager()->get(ResourceRelationServiceProxy::SERVICE_ID);
        $resourceRelationService->addService('media', MediaRelationService::class);

        $this->getServiceManager()->register(ResourceRelationServiceProxy::SERVICE_ID, $resourceRelationService);

        return common_report_Report::createSuccess('MediaManager successfully installed');
    }
}
