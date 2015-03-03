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


use oat\tao\model\media\MediaManagement;
use oat\taoMediaManager\model\fileManagement\FileManager;

class MediaManagerManagement implements MediaManagement{

    private $lang;
    private $rootClassUri;

    /**
     * get the lang of the class in case we want to filter the media on language
     * @param $data
     */
    public function __construct($data){
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoMediaManager');
        $this->lang = (isset($data['lang'])) ? $data['lang'] : '';
        $this->rootClassUri = (isset($data['rootClass'])) ? $data['rootClass'] : MEDIA_URI;
    }

    public function add($source, $fileName, $path)
    {
        $filePath = dirname($source).'/'.$fileName;

        $mediaBrowser = new MediaManagerBrowser(array('lang' => $this->lang));

        try{
            $path = trim($path,'/');
            if($path === '' || $path === '/'){
                $path = MEDIA_URI;
            }
            $class = new \core_kernel_classes_Class($path);
            if(!\tao_helpers_File::copy($source, $filePath)){
                throw new \Exception('Can\'t copy file');
            }
            $service = MediaService::singleton();
            $classUri = $class->getUri();
            $link = $service->createMediaInstance($filePath, $classUri, $this->lang);

            return $mediaBrowser->getFileInfo($link, array());

        } catch(\Exception $e){
            return array( 'error' => $e->getMessage());
        }

    }

    public function delete($filename)
    {
        $filename = preg_replace('#^\/+(.+)#', '/${1}', $filename);
        $rootClass = new \core_kernel_classes_Class($this->rootClassUri);
        $instances = $rootClass->searchInstances(array(MEDIA_LINK => $filename), array('recursive' => true));
        $instance = array_pop($instances);

        /** @var \core_kernel_classes_Resource $instance */
        $instance->delete();
        $fileManager = FileManager::getFileManagementModel();
        $deleted = $fileManager->deleteFile($filename);

        return $deleted;
    }
}