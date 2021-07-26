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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoMediaManager\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\ClientLibConfigRegistry;

class RegisterItemDataHandler extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        ClientLibConfigRegistry::getRegistry()
            ->register('taoQtiTestPreviewer/previewer/proxy/itemDataHandlers', [
                'handlers' => [
                    'injectStylesInItemData' => [
                        'id' => 'injectStylesInItemData',
                        'module' => 'taoMediaManager/richPassage/injectStylesInItemData',
                        'bundle' => 'taoMediaManager/loader/injectStylesInItemData.min',
                        'position' => null,
                        'name' => 'injectStylesInItemData',
                        'description' => 'Rich passage handler add passage custom styles to itemData',
                        'category' => 'handler',
                        'active' => true,
                        'tags' => []
                    ]
                ]
            ]);

        return \common_report_Report::createSuccess('Rich passage handler registered for taoQtiTestPreviewer');
    }
}