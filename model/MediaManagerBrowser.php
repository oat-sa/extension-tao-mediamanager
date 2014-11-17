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


use oat\tao\model\media\MediaBrowser;

class MediaManagerBrowser implements MediaBrowser{

    private $lang;

    public function __construct($datas){
        $this->lang = (isset($datas['lang'])) ? $datas['lang'] : '';
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoMediaManager');
    }

    /**
     * @param string $relPath
     * @param array $acceptableMime
     * @return array
     */
    public function getDirectory($relPath = '/', $acceptableMime = array(), $depth = 1)
    {
        if($relPath == '/'){
            $class = new \core_kernel_classes_Class(MEDIA_URI);
            $relPath = '';
        }
        else{
            if(strpos($relPath,'/') === 0){
                $relPath = substr($relPath,1);
            }
            $class = new \core_kernel_classes_Class($relPath);
        }

        $data = array(
            'path' => 'mediamanager/'.$relPath,
            'label' => $class->getLabel()
        );

        if ($depth > 0 ) {
            $children = array();
            foreach ($class->getSubClasses() as $subclass) {
                $children[] = $this->getDirectory($subclass->getUri(), $acceptableMime, $depth - 1);

            }
            $filter = array(
            );

            foreach($class->searchInstances($filter) as $instance){
                $link = $instance->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK))->__toString();
                $file = $this->getFileInfo($link, $acceptableMime);
                if(!is_null($file)){
                    $children[] = $file;
                }

            }
            $data['children'] = $children;
        }
        else{
            $data['url'] = _url('files', 'ItemContent', 'taoItems', array('lang' => $this->lang, 'path' => $relPath));
        }
        return $data;


    }

    /**
     * @param string $relPath
     * @return array
     */
    public function getFileInfo($relPath, $acceptableMime)
    {
        $file = null;
        $fileManagement = new SimpleFileManagement();
        $filePath = $fileManagement->retrieveFile($relPath);
        $mime = \tao_helpers_File::getMimeType($relPath);

        if(count($acceptableMime) == 0 || in_array($mime, $acceptableMime)){
            $file = array(
                'name' => basename($filePath),
                'relPath' => $relPath,
                'mime' => $mime,
                'size' => filesize($filePath),
                'url' => _url('download', 'ItemContent', 'taoItems', array('path' => 'mediamanager'.$relPath))
            );
        }
        return $file;

    }

    /**
     * @param string $filename
     * @return string path of the file to download
     */
    public function download($filename)
    {
        \tao_helpers_Http::returnFile($filename);
    }
}