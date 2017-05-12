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

use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use Slim\Http\Stream;

/**
 * File Managemenr relying on the tao 2.x file abstraction
 */
class TaoFileManagement extends ConfigurableService implements FileManagement
{

    protected function getFilesystem()
    {
        return new \core_kernel_fileSystem_FileSystem($this->getOption('uri'));
    }

    /**
     * @param string $link
     * @return File
     */
    protected function getFile($link)
    {
        /** @var FileReferenceSerializer $fileService */
        $fileService = $this->getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        return $fileService->unserialize($link);
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
        return $this->getFile($link)->getPrefix();
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::deleteFile()
     */
    public function deleteFile($link)
    {
        return $this->getFile($link)->delete();
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::getFileSize()
     */
    public function getFileSize($link)
    {
        return $this->getFile($link)->getSize();
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::getFileStream()
     */
    public function getFileStream($link)
    {
        return $this->getFile($link)->readPsrStream();
    }
}