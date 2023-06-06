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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

use oat\taoItems\model\user\TaoItemsRoles;
use oat\taoMediaManager\controller\Middleware\MiddlewareConfig;
use oat\taoMediaManager\model\export\service\MediaResourcePreparer;
use oat\taoMediaManager\model\sharedStimulus\encoder\SharedStimulusMediaEncoder;
use oat\taoMediaManager\scripts\install\AddAssetClassEditorRolePermission;
use oat\taoMediaManager\scripts\install\RegisterMediaResourcePreparer;
use oat\taoMediaManager\scripts\install\RegisterSharedStimulusMediaEncoder;
use oat\taoMediaManager\scripts\install\SetMediaManager;
use oat\taoMediaManager\scripts\install\RegisterXinludeHandler;
use oat\taoMediaManager\scripts\install\RegisterItemDataHandler;
use oat\taoMediaManager\model\classes\ServiceProvider\MediaServiceProvider;
use oat\taoMediaManager\model\user\TaoAssetRoles;
use oat\taoMediaManager\scripts\install\SetRolesPermissions;
use oat\taoMediaManager\scripts\install\SetupMiddlewares;
use oat\tao\model\accessControl\func\AccessRule;

$extpath = __DIR__ . DIRECTORY_SEPARATOR;
$taopath = __DIR__ . DIRECTORY_SEPARATOR . 'tao' . DIRECTORY_SEPARATOR;

return [
    'name' => 'taoMediaManager',
    'label' => 'extension-tao-mediamanager',
    'description' => 'TAO media manager extension',
    'license' => 'GPL-2.0',
    'author' => 'Open Assessment Technologies SA',
    'models' => [
        'http://www.tao.lu/Ontologies/TAOMedia.rdf'
    ],
    'managementRole' => TaoAssetRoles::MEDIA_MANAGER,
    'acl' => [
        [
            AccessRule::GRANT,
            TaoAssetRoles::MEDIA_MANAGER,
            ['ext' => 'taoMediaManager'],
        ],
        [
            AccessRule::GRANT,
            TaoItemsRoles::ITEM_AUTHOR,
            ['ext' => 'taoMediaManager'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CLASS_NAVIGATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'editClassLabel'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CLASS_NAVIGATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'index'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CLASS_NAVIGATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'getOntologyData']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CLASS_NAVIGATOR,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'files'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_VIEWER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'editInstance']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_VIEWER,
            ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'get']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_PREVIEWER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'getFile']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_PREVIEWER,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'files']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_PREVIEWER,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'download']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_EXPORTER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaExport', 'act' => 'index']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_EXPORTER,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'download'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CONTENT_CREATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'authoring']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CONTENT_CREATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaImport', 'act' => 'editMedia']
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_CONTENT_CREATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'patch'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_RESOURCE_CREATOR,
            ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'create'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_RESOURCE_CREATOR,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'fileExists'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_RESOURCE_CREATOR,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'upload'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_IMPORTER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaImport', 'act' => 'index'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_DELETER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'deleteResource'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_DELETER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'moveInstance'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ASSET_DELETER,
            ['ext' => 'taoItems', 'mod' => 'ItemContent', 'act' => 'delete'],
        ],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . '/model/ontology/taomedia.rdf',
        ],
        'php' => [
            SetMediaManager::class,
            RegisterXinludeHandler::class,
            RegisterItemDataHandler::class,
            SetRolesPermissions::class,
            SetupMiddlewares::class,
            [RegisterMediaResourcePreparer::class, ['service' => MediaResourcePreparer::class]],
            [RegisterSharedStimulusMediaEncoder::class, ['service' => SharedStimulusMediaEncoder::class]],
            AddAssetClassEditorRolePermission::class
        ]
    ],
    'update' => 'oat\\taoMediaManager\\scripts\\update\\Updater',
    'uninstall' => [
        'php' => [
            __DIR__ . '/scripts/uninstall/unsetMediaManager.php',
        ]
    ],
    'classLoaderPackages' => [
        __DIR__ . '/helpers/'
    ],
    'routes' => [
        '/taoMediaManager' => 'oat\\taoMediaManager\\controller'
    ],
    'constants' => [
        # actions directory
        "DIR_ACTIONS" => $extpath . "controller" . DIRECTORY_SEPARATOR,

        # models directory
        "DIR_MODELS" => $extpath . "models" . DIRECTORY_SEPARATOR,

        # views directory
        "DIR_VIEWS" => $extpath . "views" . DIRECTORY_SEPARATOR,

        # helpers directory
        "DIR_HELPERS" => $extpath . "helpers" . DIRECTORY_SEPARATOR,

        # default module name
        'DEFAULT_MODULE_NAME' => 'MediaManager',

        #default action name
        'DEFAULT_ACTION_NAME' => 'editMediaClass',

        #BASE PATH: the root path in the file system (usually the document root)
        'BASE_PATH' => $extpath,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . '/taoMediaManager',

        #TAO extension Paths
        'TAOVIEW_PATH' => $taopath . 'views' . DIRECTORY_SEPARATOR,
        'TAO_TPL_PATH' => $taopath . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR,
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ],
    'containerServiceProviders' => [
        MediaServiceProvider::class,
    ],
    'middlewares' => [
        MiddlewareConfig::class,
    ],
];
