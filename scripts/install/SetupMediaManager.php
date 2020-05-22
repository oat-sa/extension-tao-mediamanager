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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoMediaManager\scripts\install;

use common_report_Report;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\media\MediaService;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;

class SetupMediaManager extends InstallAction
{

    public function __invoke($params)
    {
        $serviceManager = $this->getServiceManager();
        $fsService = $serviceManager->get(FileSystemService::SERVICE_ID);
        $fsService->createFileSystem('mediaManager');

        $flySystemManagement = new FlySystemManagement([FlySystemManagement::OPTION_FS => 'mediaManager']);
        $serviceManager->register(FileManagement::SERVICE_ID, $flySystemManagement);

        $mediaManager = new MediaSource();
        $serviceManager->get(MediaService::SERVICE_ID)->addMediaSource($mediaManager);

        if ($fsService->hasDirectory('memory')) {
            $dirs = $fsService->getOption(FileSystemService::OPTION_DIRECTORIES);
            $dirs[CommandFactory::DEFAULT_DIRECTORY] = 'memory';
            $fsService->setOption(FileSystemService::OPTION_DIRECTORIES, $dirs);
        } else {
            $fileSystem = $fsService->createFileSystem(CommandFactory::DEFAULT_DIRECTORY);
        }

        $serviceManager->register(FileSystemService::SERVICE_ID, $fsService);

        return common_report_Report::createSuccess('Successfully installed');
    }
}
