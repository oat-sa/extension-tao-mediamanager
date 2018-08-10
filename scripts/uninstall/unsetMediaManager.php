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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoMediaManager\scripts\uninstall;

use oat\oatbox\extension\UninstallAction;
use oat\tao\model\media\MediaService;
use oat\taoMediaManager\model\fileManagement\FileManagement;

class UninstallMediaManager extends UninstallAction
{
    public function __invoke($params)
    {
        MediaService::singleton()->removeMediaSource('mediamanager');
        $this->unregisterService(FileManagement::SERVICE_ID);

        return \common_report_Report::createInfo('Media manager extension successfully uninstalled.');
    }

}