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

class MediaManagerManagement implements MediaManagement
{

    private $lang;
    private $rootClassUri;

    /**
     * get the lang of the class in case we want to filter the media on language
     * @param $data
     */
    public function __construct($data)
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoMediaManager');
        $this->lang = (isset($data['lang'])) ? $data['lang'] : '';
        $this->rootClassUri = (isset($data['rootClass'])) ? $data['rootClass'] : MEDIA_URI;

    }


    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\media\MediaManagement::add
     */
    public function add($source, $fileName, $parent)
    {
        if (!file_exists($source)) {
            throw new \tao_models_classes_FileNotFoundException('File ' . $source . ' not found');
        }
        $parent = \tao_helpers_uri::decode($parent);
        if ($parent === '') {
            $parent = MEDIA_URI;
        }
        $class = new \core_kernel_classes_Class($parent);
        if (!$class->exists()) {
            throw new \common_exception_Error('Class ' . $parent . ' not found');
        }
        $service = MediaService::singleton();
        $instanceUri = $service->createMediaInstance($source, $class->getUri(), $this->lang, $fileName);

        return $this->getFileInfo($instanceUri);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\media\MediaManagement::delete
     */
    public function delete($link)
    {
        /** @var \core_kernel_classes_Resource $instance */
        $instance = new \core_kernel_classes_Class(\tao_helpers_Uri::decode($link));
        $fileLink = $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK));
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;


        $instance->delete();
        $fileManager = FileManager::getFileManagementModel();
        $deleted = $fileManager->deleteFile($fileLink);

        return $deleted;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\media\MediaBrowser::getDirectory
     */
    public function getDirectory($parentLink = '', $acceptableMime = array(), $depth = 1)
    {
        if ($parentLink == '') {
            $class = new \core_kernel_classes_Class($this->rootClassUri);
            $parentLink = '';
        } else {
            $class = new \core_kernel_classes_Class(\tao_helpers_Uri::decode($parentLink));
        }

        $data = array(
            'path' => 'taomedia://mediamanager/' . \tao_helpers_Uri::encode($parentLink),
            'label' => $class->getLabel()
        );

        if ($depth > 0) {
            $children = array();
            foreach ($class->getSubClasses() as $subclass) {
                $children[] = $this->getDirectory($subclass->getUri(), $acceptableMime, $depth - 1);

            }

            //add a filter for example on language (not for now)
            $filter = array();

            foreach ($class->searchInstances($filter) as $instance) {
                $file = $this->getFileInfo($instance->getUri());
                if (!is_null($file) && (count($acceptableMime) == 0 || in_array($file['mime'], $acceptableMime)) ) {
                    //add the alt text to file array
                    $altArray = $instance->getPropertyValues(new \core_kernel_classes_Property(MEDIA_ALT_TEXT));
                    if (count($altArray) > 0) {
                        $file['alt'] = $altArray[0];
                    }
                    $children[] = $file;
                }

            }
            $data['children'] = $children;
        } else {
            $data['url'] = \tao_helpers_Uri::url(
                'files',
                'ItemContent',
                'taoItems',
                array('lang' => $this->lang, 'path' => $parentLink)
            );
        }
        return $data;


    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\media\MediaBrowser::getFileInfo
     */
    public function getFileInfo($link)
    {
        //get the media link from the resource
        $resource = new \core_kernel_classes_Resource(\tao_helpers_Uri::decode($link));
        if($resource->exists()){
            $fileLink = $resource->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK));
            $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
            $file = null;
            $fileManagement = FileManager::getFileManagementModel();
            $filePath = $fileManagement->retrieveFile($fileLink);
            $mime = \tao_helpers_File::getMimeType($filePath);

            if (file_exists($filePath)) {
                $file = array(
                    'name' => basename($filePath),
                    'uri' => 'taomedia://mediamanager/'.\tao_helpers_Uri::encode($link),
                    'mime' => $mime,
                    'size' => filesize($filePath),
                );
            }
            return $file;
        }
        else{
            throw new \Exception('Resource ' . $resource->getUri() . ' can not be found');
        }

    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\media\MediaBrowser::download
     */
    public function download($link)
    {
        //get the media link from the resource
        $resource = new \core_kernel_classes_Class(\tao_helpers_Uri::decode($link));
        $fileLink = $resource->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK));
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        $fileManagement = FileManager::getFileManagementModel();
        $filePath = $fileManagement->retrieveFile($fileLink);
        return $filePath;
    }
}