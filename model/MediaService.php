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

namespace oat\taoMediaManager\model;

use oat\taoMediaManager\model\fileManagement\FileManager;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager

 */
class MediaService extends \tao_models_classes_GenerisService
{


    // --- OPERATIONS ---

    /**
     * Short description of method __construct
     *
     * @access public
     */
    protected function __construct(){
        parent::__construct();
    }

    public function getRootClass(){
        return new \core_kernel_classes_Class(MEDIA_URI);
    }

    /**
     * Create a media instance from a file, and define its class and language
     * @param string $filetmp
     * @param string $classUri
     * @param string $language
     */
    public function createMediaInstance($filetmp, $classUri, $language){
        $fileManager = FileManager::getFileManagementModel();
        $link = $fileManager->storeFile($filetmp);

        if($link !== false){
            $clazz = new \core_kernel_classes_Class($classUri);
            $instance = $this->createInstance($clazz, basename($filetmp));
            /** @var $instance  \core_kernel_classes_Resource*/
            if(!is_null($instance) && $instance instanceof \core_kernel_classes_Resource){
                $instance->setPropertyValue(new \core_kernel_classes_Property(MEDIA_LINK), $link);
                $instance->setPropertyValue(new \core_kernel_classes_Property(MEDIA_LANGUAGE), $language);
            }
        }
        return $link;

    }

    /**
     * Edit a media instance with a new file and/or a new language
     * @param $filetmp
     * @param $instanceUri
     * @param $language
     */
    public function editMediaInstance($filetmp, $instanceUri, $language){
        $fileManager = FileManager::getFileManagementModel();
        $link = $fileManager->storeFile($filetmp);

        if($link !== false){
            $instance = new \core_kernel_classes_Class($instanceUri);
            /** @var $instance  \core_kernel_classes_Resource*/
            if(!is_null($instance) && $instance instanceof \core_kernel_classes_Resource){
                $instance->editPropertyValues(new \core_kernel_classes_Property(MEDIA_LINK), $link);
                $instance->editPropertyValues(new \core_kernel_classes_Property(MEDIA_LANGUAGE), $language);
            }
        }

    }
}
