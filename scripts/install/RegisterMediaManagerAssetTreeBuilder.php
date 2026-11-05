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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts\install;

use common_report_Report;
use oat\oatbox\extension\InstallAction;
use oat\taoItems\model\media\AssetTreeBuilder;
use oat\taoMediaManager\model\media\MediaManagerAssetTreeBuilder;

class RegisterMediaManagerAssetTreeBuilder extends InstallAction
{
    public function __invoke($params = []): common_report_Report
    {
        $this->getServiceManager()->register(
            AssetTreeBuilder::SERVICE_ID,
            new MediaManagerAssetTreeBuilder(
                [
                    AssetTreeBuilder::OPTION_PAGINATION_LIMIT => 15,
                ]
            )
        );

        return common_report_Report::createSuccess('MediaManagerAssetTreeBuilder registered.');
    }
}
