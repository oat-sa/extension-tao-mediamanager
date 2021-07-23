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

use oat\taoMediaManager\scripts\install\SetMediaManager;
use oat\taoMediaManager\model\classes\user\TaoAssetRoles;
use oat\tao\model\accessControl\func\AccessRule;
use oat\taoMediaManager\scripts\install\SetRolesPermissions;

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
            TaoAssetRoles::ITEM_AUTHOR,
            ['ext' => 'taoMediaManager'],
        ],
        [
            AccessRule::GRANT,
            TaoAssetRoles::ITEM_AUTHOR,
            ['ext' => 'taoMediaManager', 'mod' => 'SharedStimulus', 'act' => 'create'],
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
            TaoAssetRoles::ASSET_EXPORTER,
            ['ext' => 'taoMediaManager', 'mod' => 'MediaExport', 'act' => 'index']
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
            ['ext' => 'taoMediaManager', 'mod' => 'MediaManager', 'act' => 'editInstance'],
        ],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . '/model/ontology/taomedia.rdf',
        ],
        'php' => [
            SetMediaManager::class,
            SetRolesPermissions::class,
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
    ]
];
