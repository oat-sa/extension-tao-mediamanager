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
use oat\taoMediaManager\model\MediaService;
use oat\tao\model\media\MediaService as TaoMediaService;
use oat\taoMediaManager\model\MediaSource;

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
            // mediaSources set in 0.2
            $currentVersion = '0.1.1';
        }
        if ($currentVersion == '0.1.1') {

            FileManager::setFileManagementModel(new SimpleFileManagement());
            // mediaSources unset in 0.2

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

        if ($currentVersion == '0.1.4') {

            //modify config files due to the new interfaces relation
            $tao = \common_ext_ExtensionsManager::singleton()->getExtensionById('tao');
            $tao->unsetConfig('mediaManagementSources');
            $tao->unsetConfig('mediaBrowserSources');
            
            TaoMediaService::singleton()->addMediaSource(new MediaSource());

            //modify links in item content
            $service = \taoItems_models_classes_ItemsService::singleton();
            $items = $service->getAllByModel('http://www.tao.lu/Ontologies/TAOItem.rdf#QTI');

            foreach($items as $item){
                $itemContent = $service->getItemContent($item);
                $itemContent = preg_replace_callback('/src="mediamanager\/([^"]+)"/', function($matches){
                        $mediaClass = MediaService::singleton()->getRootClass();
                        $medias = $mediaClass->searchInstances(array(
                                MEDIA_LINK => $matches[1]
                            ), array('recursive' => true));
                        $media = array_pop($medias);
                        return 'src="taomedia://mediamanager/' . \tao_helpers_Uri::encode($media->getUri()) . '"';

                    }, $itemContent);

                $itemContent = preg_replace_callback('/src="local\/([^"]+)"/', function($matches){
                        return 'src="' . $matches[1] . '"';

                    }, $itemContent);

                $service->setItemContent($item, $itemContent);

            }

            $currentVersion = '0.2.0';

        }

        return $currentVersion;
    }
}
