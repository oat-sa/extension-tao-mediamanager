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
namespace oat\taoMediaManager\model\fileManagement;


class SimpleFileManagement implements FileManagement{
    
    private function getBaseDir() {
        return dirname(dirname(__DIR__)).'/media/';
    }

    /**
     * store the file and provide a link to retrieve it
     * @param string $filePath the entire path to the file
     * @return string a link to the file in order to retrieve it later
     * @throws \common_exception_Error
     */
    public function storeFile($filePath)
    {
        $path = $this->getBaseDir();
        // create media folder if doesn't exist
        if(!is_dir($path)){
            mkdir($path);
        }

        $fileName = \tao_helpers_File::getSafeFileName(basename($filePath));
        if(!is_dir($path.$fileName)){
            if(!@copy($filePath, $path.$fileName)){
                throw new \common_exception_Error('Unable to move uploaded file');
            }
            return $fileName;
        }
        return false;
    }

    /**
     * get the link and return the file that match it
     * @param string $link the link provided by storeFile
     * @return string $filename the file that match the link
     */
    public function retrieveFile($link)
    {
        if (!\tao_helpers_File::securityCheck($link)) {
            throw new \common_exception_Error('Unsecure file link found');
        }
        return $this->getBaseDir().$link;
    }

    /**
     * @param $link
     * @return boolean if the deletion was successful or not
     */
    public function deleteFile($link)
    {
        return @unlink($this->getBaseDir().$link);
    }
}