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


class SimpleFileManagement implements FileManagement{

    /**
     * store the file and provide a link to retrieve it
     * @param string $filePath the entire path to the file
     * @return string a link to the file in order to retrieve it later
     * @throws \common_exception_Error
     */
    public function storeFile($filePath)
    {
        $baseDir = dirname(__DIR__);
        $relPath = '/media/';

        $fileName = \tao_helpers_File::getSafeFileName(basename($filePath));

        // create media folder if doesn't exist
        if(!is_dir($baseDir.$relPath)){
            mkdir($baseDir.$relPath);
        }

        if(!is_dir($baseDir.$relPath.$fileName)){
            if(!rename($filePath, $baseDir.$relPath.$fileName)){
                throw new \common_exception_Error('Unable to move uploaded file');
            }
            return $baseDir.$relPath.$fileName;
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
        return $link;
    }
}