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

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoMediaManager\controller\MediaImport;
use oat\taoMediaManager\controller\MediaManager;
use oat\taoMediaManager\model\user\TaoAssetRoles;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;

final class Version202107261845541888_taoMediaManager extends AbstractMigration
{
    private const CONFIG = [
        SetRolesAccess::CONFIG_RULES => [
            TaoAssetRoles::ASSET_RESOURCE_CREATOR => [
                ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'create'],
            ],
            TaoAssetRoles::ASSET_IMPORTER => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaImport', 'act' => 'index'],
            ],
            TaoAssetRoles::ASSET_DELETER => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'deleteResource'],
            ],
        ],
        SetRolesAccess::CONFIG_PERMISSIONS => [
            MediaManager::class => [
                'isPreviewEnabled' => [
                    TaoAssetRoles::ASSET_VIEWER => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_PREVIEWER => ActionAccessControl::READ,
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

    public function getDescription(): string
    {
        return 'Create new asset management roles and assign permissions to them';
    }

    public function up(Schema $schema): void
    {
        OntologyUpdater::syncModels();

        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess([
            '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
        ]);
    }

    public function down(Schema $schema): void
    {
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess([
            '--' . SetRolesAccess::OPTION_REVOKE,
            '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
        ]);
    }
}
