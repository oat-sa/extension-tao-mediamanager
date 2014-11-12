<?php
/*
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
 * Copyright (c) 2008-2010 (original work) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *
 */

namespace oat\taoMediaManager\model;
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
     * @author Joel Bout, <joel@taotesting.com>
     * @return void
     */
    protected function __construct(){
        parent::__construct();
    }

    public function getRootClass(){
        return new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');
    }

    public function createMediaInstance($filetmp, $classUri, $language){
        $fileManager = new SimpleFileManagement();
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

    }

    public function createTreeFromZip($dirs, $base, $parent){

        //create the base class
        $clazz = new \core_kernel_classes_Class($parent);
        $baseClazz = $this->createSubClass($clazz, $base);

        $parents[$base] = $baseClazz;

        foreach($dirs as $parent => $children){
            foreach($children as $child){
                if(isset($parents[$parent])){
                    $childClazz = $this->createSubClass($parents[$parent], $child);
                    $parents[$child] = $childClazz;
                }
            }
        }

        return $parents;

    }

}
