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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts\install;

use taoItems_actions_ItemContent;
use oat\oatbox\extension\InstallAction;
use oat\taoMediaManager\controller\MediaImport;
use oat\taoMediaManager\controller\MediaManager;
use oat\taoMediaManager\model\user\TaoAssetRoles;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;

class SetRolesPermissions extends InstallAction
{
    private const CONFIG = [
        SetRolesAccess::CONFIG_PERMISSIONS => [
            MediaManager::class => [
                'editClassLabel' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::READ
                ],
                'editInstance' => [
                    TaoAssetRoles::ASSET_VIEWER => ActionAccessControl::READ,
                    TaoAssetRoles::ASSET_PROPERTIES_EDITOR => ActionAccessControl::WRITE,
                ],
                'isPreviewEnabled' => [
                    TaoAssetRoles::ASSET_VIEWER => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_PREVIEWER => ActionAccessControl::READ,
                ],
            ],
            taoItems_actions_ItemContent::class => [
                'files' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_PREVIEWER => ActionAccessControl::READ,
                ],
                'delete' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_DELETER => ActionAccessControl::WRITE,
                ],
                'upload' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_RESOURCE_CREATOR => ActionAccessControl::WRITE,
                ],
                'previewAsset' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_PREVIEWER => ActionAccessControl::READ,
                ],
                'downloadAsset' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_EXPORTER => ActionAccessControl::READ,
                ],
                'uploadAsset' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_IMPORTER => ActionAccessControl::WRITE,
                ],
                'deleteAsset' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_DELETER => ActionAccessControl::WRITE,
                ],
            ],
            MediaImport::class => [
                'editMedia' => [
                    TaoAssetRoles::ASSET_VIEWER => ActionAccessControl::READ,
                    TaoAssetRoles::ASSET_CONTENT_CREATOR => ActionAccessControl::WRITE,
                ],
            ],
        ],
    ];

    public function __invoke($params = [])
    {
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess([
            '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
        ]);
    }
}
