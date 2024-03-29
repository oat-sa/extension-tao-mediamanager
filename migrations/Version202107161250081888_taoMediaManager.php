<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use taoItems_actions_ItemContent;
use oat\taoMediaManager\controller\MediaManager;
use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\model\user\TaoAssetRoles;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;
use oat\tao\scripts\update\OntologyUpdater;
use oat\tao\model\accessControl\ActionAccessControl;

final class Version202107161250081888_taoMediaManager extends AbstractMigration
{
    private const CONFIG = [
        SetRolesAccess::CONFIG_RULES => [
            TaoAssetRoles::ASSET_CLASS_NAVIGATOR => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'editClassLabel'],
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'index'],
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'getOntologyData']
            ],
            TaoAssetRoles::ASSET_VIEWER => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'editInstance'],
                ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'get'],
            ],
            TaoAssetRoles::ASSET_EXPORTER => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaExport', 'act' => 'index'],
            ],
            TaoAssetRoles::ASSET_PREVIEWER => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'getFile'],
                ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'files'],
                ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'download'],
            ],
            TaoAssetRoles::ASSET_CONTENT_CREATOR => [
                ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'authoring'],
                ['ext' => 'taoMediaManager', 'mod' => 'MediaImport', 'act' => 'editMedia'],
                ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'patch'],
            ],
        ],
        SetRolesAccess::CONFIG_PERMISSIONS => [
            MediaManager::class => [
                'editClassLabel' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::READ
                ],
                'editInstance' => [
                    TaoAssetRoles::ASSET_VIEWER => ActionAccessControl::READ,
                    TaoAssetRoles::ASSET_PROPERTIES_EDITOR => ActionAccessControl::WRITE,
                ],
            ],
            taoItems_actions_ItemContent::class => [
                'files' => [
                    TaoAssetRoles::ASSET_CLASS_NAVIGATOR => ActionAccessControl::DENY,
                    TaoAssetRoles::ASSET_PREVIEWER => ActionAccessControl::READ,
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
