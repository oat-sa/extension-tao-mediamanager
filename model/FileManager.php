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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\taoMediaManager\model;

class FileManager{

    const CONFIG_KEY = 'fileManager';

    /**
     * @var array
     */
    private static $fileManager = null;


    /**
     * @return FileManagement
     */
    public static function getPermissionModel() {
        if (is_null(self::$fileManager)) {
            $implementation = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoMediaManager')->getConfig(self::CONFIG_KEY);
            if (class_exists($implementation)) {
                self::$fileManager = new $implementation();
            } else {
                \common_Logger::w('No file manager implementation found');
                self::$fileManager = new SimpleFileManagement();
            }
        }
        return self::$fileManager;
    }

    /**
     * @param FileManagement $model
     */
    public static function setPermissionModel(FileManagement $model) {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoMediaManager')->setConfig(self::CONFIG_KEY, get_class($model));
    }

} 