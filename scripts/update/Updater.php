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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\scripts\update;

use oat\tao\model\media\MediaSource;
use \oat\taoMediaManager\model\fileManagement\FileManager;
use \oat\taoMediaManager\model\fileManagement\SimpleFileManagement;
use oat\tao\scripts\update\OntologyUpdater;

class Updater extends \common_ext_ExtensionUpdater {

    /**
     *
     * @param string $initialVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion) {

        $currentVersion = $initialVersion;

        //migrate from 0.1 to 0.1.1
        if ($currentVersion == '0.1') {

            MediaSource::addMediaSource('mediamanager', 'oat\taoMediaManager\model\MediaManagerBrowser', 'browser');
            MediaSource::addMediaSource('mediamanager', 'oat\taoMediaManager\model\MediaManagerManagement', 'management');

            $currentVersion = '0.1.1';
        }
        if ($currentVersion == '0.1.1') {

            FileManager::setFileManagementModel(new SimpleFileManagement());
            $tao = \common_ext_ExtensionsManager::singleton()->getExtensionById('tao');
            $configs = $tao->hasConfig('mediaSources')? $tao->getConfig('mediaSources'): array();;
            if(!empty($configs)){
                $tao->unsetConfig('mediaSources');
            }

            $currentVersion = '0.1.2';
        }
        if ($currentVersion == '0.1.2') {

            //add alt text to media manager
            $file = dirname(__FILE__).DIRECTORY_SEPARATOR.'alt_text.rdf';

            $adapter = new \tao_helpers_data_GenerisAdapterRdf();
            if($adapter->import($file)){
                $currentVersion = '0.1.3';
            } else{
                \common_Logger::w('Import failed for '.$file);
            }

        }
        

        if ($currentVersion == '0.1.3') {
            
            OntologyUpdater::correctModelId(dirname(__FILE__).DIRECTORY_SEPARATOR.'alt_text.rdf');
            $currentVersion = '0.1.4';
        
        }

        return $currentVersion;
    }
}
