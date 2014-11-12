<?php
/**
 * Created by Antoine on 11/11/14
 * at 15:11
 */

namespace oat\taoMediaManager\model;


class SimpleFileManagement implements FileManagement{

    /**
     * @param string $filePath the entire path to the file
     * @return string a link to the file in order to retrieve it later
     */
    public function storeFile($filePath)
    {
        $baseDir = dirname(__DIR__);
        $relPath = '/media/';

        $fileName = \tao_helpers_File::getSafeFileName(basename($filePath));

        if(!is_dir($baseDir.$relPath.$fileName)){
            if(!rename($filePath, $baseDir.$relPath.$fileName)){
                throw new \common_exception_Error('Unable to move uploaded file');
            }
            return $baseDir.$relPath.$fileName;
        }
        return false;
    }

    /**
     *
     * @param string $link the link provided by storeFile
     * @return the file that match the link
     */
    public function retrieveFile($link)
    {
        return $link;
    }
}