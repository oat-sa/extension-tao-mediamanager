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
use oat\taoMediaManager\model\user\TaoAssetRoles;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;
use taoItems_actions_ItemContent;

final class Version202108091845541888_taoMediaManager extends AbstractMigration
{
    private const CONFIG = [
        SetRolesAccess::CONFIG_RULES => [
            TaoAssetRoles::ASSET_EXPORTER => [
                [
                    'ext' => 'taoItems',
                    'mod' => 'ItemContent',
                    'act' => 'download'
                ],
            ],
        ],
        SetRolesAccess::CONFIG_PERMISSIONS => [
            taoItems_actions_ItemContent::class => [
                'download' => [
                    TaoAssetRoles::ASSET_VIEWER => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_EXPORTER => ActionAccessControl::READ,
                ],
            ],
        ],
    ];

    public function getDescription(): string
    {
        return 'Give proper permission for delete and download assets';
    }

    public function up(Schema $schema): void
    {
        OntologyUpdater::syncModels();

        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess(
            [
                '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess(
            [
                '--' . SetRolesAccess::OPTION_REVOKE,
                '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
            ]
        );
    }
}
