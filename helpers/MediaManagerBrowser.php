<?php
/**
 * Created by Antoine on 13/11/14
 * at 16:07
 */

namespace oat\taoMediaManager\helpers;


use oat\tao\model\media\MediaBrowser;
use oat\taoMediaManager\model\SimpleFileManagement;

class MediaManagerBrowser implements MediaBrowser{

    private $lang;

    public function __construct($datas){
        $this->lang = (isset($datas['lang'])) ? $datas['lang'] : '';
    }

    /**
     * @param string $relPath
     * @param array $acceptableMime
     * @return array
     */
    public function getDirectory($relPath = '/', $acceptableMime = array(), $depth = 1)
    {
        if($relPath == '/'){
            $class = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAOMedia.rdf#Media');
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
            $class->searchInstances();
            $filter = array('http://www.tao.lu/Ontologies/TAOMedia.rdf#Language' => $this->lang);
            $fileManagement = new SimpleFileManagement();
            foreach($class->searchInstances($filter) as $instances){
                $fullPath = $fileManagement->retrieveFile($instances->getUniquePropertyValue(new \core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAOMedia.rdf#Link'))->__toString());
                $file = $this->getFileInfo($fullPath, $acceptableMime);
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

        $mime = \tao_helpers_File::getMimeType($relPath);

        if(count($acceptableMime) == 0 || in_array($mime, $acceptableMime)){
            $file = array(
                'name' => basename($relPath),
                'mime' => $mime,
                'size' => filesize($relPath),
                'url' => _url('download', 'ItemContent', 'taoItems', array('lang' => $this->lang, 'path' => $relPath))
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
        // TODO: Implement download() method.
    }
}