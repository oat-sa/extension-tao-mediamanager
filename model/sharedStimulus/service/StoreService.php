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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use League\Flysystem\FilesystemInterface;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;

class StoreService extends ConfigurableService
{
    /**
     * name of sub-directory to store stylesheets
     */
    public const CSS_DIR_NAME = 'css';

    /**
     * @param string|File $stimulusXmlSourceFile
     */
    public function store($stimulusXmlSourceFile, string $stimulusFilename, array $cssFiles = []): string
    {
        $fs = $this->getFileSystem();

        $dirname = $this->getUniqueName($stimulusFilename);
        $fs->createDir($dirname);

        $stimulusXmlStream = $stimulusXmlSourceFile instanceof File ? $stimulusXmlSourceFile->readStream() : fopen($stimulusXmlSourceFile, 'r');

        $fs->putStream($dirname . DIRECTORY_SEPARATOR . $stimulusFilename, $stimulusXmlStream);

        if (count($cssFiles)) {
            $fs->createDir($dirname . DIRECTORY_SEPARATOR . self::CSS_DIR_NAME);
            foreach ($cssFiles as $file) {
                $fs->putStream(
                    $dirname . DIRECTORY_SEPARATOR . self::CSS_DIR_NAME . DIRECTORY_SEPARATOR . basename($file),
                    fopen($file, 'r')
                );
            }
        }

        return $dirname;
    }

    protected function getUniqueName(string $name): string
    {
        return uniqid(hash('crc32', $name));
    }

    private function getFileSystem(): FilesystemInterface
    {
        return $this->getFileSystemService()
            ->getFileSystem($this->getFlySystemManagement()->getOption(FlySystemManagement::OPTION_FS));
    }

    private function getFileSystemService(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    private function getFlySystemManagement(): FlySystemManagement
    {
        return $this->getServiceLocator()->get(FlySystemManagement::SERVICE_ID);
    }
}
