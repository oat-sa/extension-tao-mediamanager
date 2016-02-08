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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

use oat\oatbox\service\ServiceManager;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\tao\model\media\MediaService;
use oat\taoMediaManager\model\MediaSource;

$serviceManager = ServiceManager::getServiceManager();
$fsService = $serviceManager->get(FileSystemService::SERVICE_ID); 
$fsService->createLocalFileSystem('mediaManager');
$serviceManager->register(FileSystemService::SERVICE_ID, $fsService);

$flySystemManagement = new FlySystemManagement(array(FlySystemManagement::OPTION_FS => 'mediaManager'));
$serviceManager->register(FileManagement::SERVICE_ID, $flySystemManagement);

$mediaManager = new MediaSource();
MediaService::singleton()->addMediaSource($mediaManager);

$mediaRenderer = new \oat\taoMediaManager\model\rendering\BaseRenderer();
$serviceManager->register(\oat\tao\model\media\MediaRendererInterface::SERVICE_ID, $mediaRenderer);
