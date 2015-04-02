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

use oat\oatbox\Configurable;

class TaoFileManagement extends Configurable implements FileManagement
{

    protected function getFilesystem()
    {
        return new \core_kernel_fileSystem_FileSystem($this->getOption('uri'));
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::storeFile()
     */
    public function storeFile($filePath, $label)
    {
        $file = $this->getFilesystem()->spawnFile($filePath, $label);
        if (is_null($file)) {
            throw new \common_Exception('Unable to spawn file for ' . $filePath);
        }
        return $file->getUri();
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::retrieveFile()
     */
    public function retrieveFile($link)
    {
        $file = new \core_kernel_file_File($link);
        return $file->getAbsolutePath();
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::deleteFile()
     */
    public function deleteFile($link)
    {
        $file = new \core_kernel_file_File($link);
        unlink($file->getAbsolutePath());
        return $file->delete();
    }
}