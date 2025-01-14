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
 * Copyright (c) 2014-2021 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\model\fileManagement;

use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FilesystemException;
use oat\oatbox\filesystem\FilesystemInterface;
use oat\oatbox\service\ConfigurableService;
use Slim\Http\Stream;
use Psr\Http\Message\StreamInterface;
use oat\oatbox\filesystem\FileSystemService;

class FlySystemManagement extends ConfigurableService implements FileManagement
{
    public const OPTION_FS = 'fs';

    /**
     * @param string|File $fileSource
     * @param string $label
     * @return string
     * @throws FilesystemException
     */
    public function storeFile($fileSource, $label)
    {
        $filename = $this->getUniqueFilename($label);
        $pathInfo = pathinfo($filename);
        $baseName = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        $filenameWithPath = $baseName . '/' . $baseName . $extension;   
        $stream = $fileSource instanceof File ? $fileSource->readStream() : fopen($fileSource, 'r');
        $this->getFileSystem()->writeStream($filenameWithPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $filenameWithPath;
    }

    public function deleteDirectory(string $directoryPath): bool
    {
        try {
            $this->getFilesystem()->deleteDirectory($directoryPath);
            return true;
        } catch (FilesystemException $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }

    public function getFileSize($link)
    {
        try {
            return $this->getFilesystem()->fileSize($link);
        } catch (FilesystemException $e) {
            $this->logWarning($e->getMessage());
            return null;
        }
    }

    /**
     *
     * @param string $link
     * @return StreamInterface
     */
    public function getFileStream($link)
    {
        $resource = $this->getFilesystem()->readStream($link);
        return new Stream($resource);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::retrieveFile()
     */
    public function retrieveFile($link)
    {
        $this->logWarning('Deprecated');
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoMediaManager\model\fileManagement\FileManagement::deleteFile()
     */
    public function deleteFile($link)
    {
        try {
            $this->getFilesystem()->delete($link);
            return true;
        } catch (FilesystemException $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }

    protected function getFilesystem(): FilesystemInterface
    {
        $fs = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
        return $fs->getFileSystem($this->getOption(self::OPTION_FS));
    }

    /**
     * Create a new unique filename based on an existing filename
     *
     * @param string $fileName
     * @return string
     */
    protected function getUniqueFilename($fileName)
    {
        $returnValue = uniqid(hash('crc32', $fileName));

        $ext = @pathinfo($fileName, PATHINFO_EXTENSION);
        if (!empty($ext)) {
            $returnValue .= '.' . $ext;
        }

        return $returnValue;
    }
}
