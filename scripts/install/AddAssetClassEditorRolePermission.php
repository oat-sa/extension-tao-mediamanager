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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts\install;

use common_ext_action_InstallAction;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;
use oat\taoMediaManager\controller\MediaManager;
use oat\taoMediaManager\model\user\TaoAssetRoles;

class AddAssetClassEditorRolePermission extends common_ext_action_InstallAction
{
    public const CONFIG = [
        SetRolesAccess::CONFIG_PERMISSIONS => [
            MediaManager::class => [
                'editClassLabel' => [
                    TaoAssetRoles::ASSET_CLASS_EDITOR_ROLE => ActionAccessControl::WRITE
                ],
            ]
        ],
    ];

    public function __invoke($params)
    {
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess([
            '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
        ]);
    }
}
