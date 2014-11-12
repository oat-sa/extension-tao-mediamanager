<?php
namespace oat\taoMediaManager\helpers;

class MediaManagerBrowser {

    public static function buildDirectory(\core_kernel_classes_Resource $item, $lang, $relPath = '/', $depth = 1, $filters = array()) {

        $baseDir = \taoItems_models_classes_ItemsService::singleton()->getItemFolder($item, $lang);
        $path = $baseDir.ltrim($relPath, '/');

        $data = array(
            'path' => $relPath
        );
        if ($depth > 0 ) {
            $children = array();
            if (is_dir($path)) {
                foreach (new \DirectoryIterator($path) as $fileinfo) {
                    if (!$fileinfo->isDot()) {
                        $subPath = rtrim($relPath, '/').'/'.$fileinfo->getFilename();
                        if ($fileinfo->isDir()) {
                            $children[] = self::buildDirectory($item, $lang, $subPath, $depth-1, $filters);
                        } else {
                            $file = self::buildFile($item, $lang, $subPath, $filters);
                            if(!is_null($file)){
                                $children[] = $file;
                            }
                        }
                    }
                }
            } else {
                \common_Logger::w('"'.$path.'" is not a directory');
            }
            $data['children'] = $children;
        } else {
            $data['url'] = _url('files', 'ItemContent', 'taoItems', array('uri' => $item->getUri(),'lang' => $lang, 'path' => $relPath));
        }
        return $data;
    }


    public static function buildFile(\core_kernel_classes_Resource $item, $lang, $relPath, $filters = array()) {
        $file = null;
        $baseDir = \taoItems_models_classes_ItemsService::singleton()->getItemFolder($item, $lang);
        $path = $baseDir.ltrim($relPath, '/');
        $mime = \tao_helpers_File::getMimeType($path);

        if(count($filters) == 0 || in_array($mime, $filters)){
            $file = array(
                'name' => basename($path),
                'mime' => $mime,
                'size' => filesize($path),
                'url' => _url('download', 'ItemContent', 'taoItems', array('uri' => $item->getUri(),'lang' => $lang, 'path' => $relPath))
            );
        }
        return $file;
    }

} 