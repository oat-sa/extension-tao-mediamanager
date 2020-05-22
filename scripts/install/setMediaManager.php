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

use oat\oatbox\event\EventManager;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoItems\model\event\ItemRemovedEvent;
use oat\taoItems\model\event\ItemUpdatedEvent;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\tao\model\media\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\relation\event\MediaRelationListener;
use oat\taoMediaManager\model\relation\event\MediaRemovedEvent;
use oat\taoMediaManager\model\relation\event\MediaSavedEvent;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfItemRelationMap;
use oat\taoMediaManager\model\relation\repository\rdf\map\RdfMediaRelationMap;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;

/** @var ServiceManager $serviceManager */
$serviceManager = ServiceManager::getServiceManager();

$fsService = $serviceManager->get(FileSystemService::SERVICE_ID);
$fsService->createFileSystem('mediaManager');

$serviceManager->register(FileSystemService::SERVICE_ID, $fsService);
$serviceManager->register(
    FileManagement::SERVICE_ID,
    new FlySystemManagement(
        [
            FlySystemManagement::OPTION_FS => 'mediaManager'
        ]
    )
);
$serviceManager->register(
    MediaRelationRepositoryInterface::SERVICE_ID,
    new RdfMediaRelationRepository(
        [
            RdfMediaRelationRepository::MAP_OPTION => [
                new RdfItemRelationMap(),
                new RdfMediaRelationMap()
            ]
        ]
    )
);

/** @var EventManager $eventManager */
$eventManager = $serviceManager->get(EventManager::SERVICE_ID);
$eventManager->attach(ItemUpdatedEvent::class, [MediaRelationListener::class, 'whenItemIsUpdated']);
$eventManager->attach(ItemRemovedEvent::class, [MediaRelationListener::class, 'whenItemIsRemoved']);
$eventManager->attach(MediaRemovedEvent::class, [MediaRelationListener::class, 'whenMediaIsRemoved']);
$eventManager->attach(MediaSavedEvent::class, [MediaRelationListener::class, 'whenMediaIsSaved']);

$serviceManager->register(EventManager::SERVICE_ID, $eventManager);

$mediaManager = new MediaSource();
MediaService::singleton()->addMediaSource($mediaManager);
