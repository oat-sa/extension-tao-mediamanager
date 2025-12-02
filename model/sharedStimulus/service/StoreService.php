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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2021-2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FilesystemInterface;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;

class StoreService extends ConfigurableService
{
    /**
     * Name of subdirectory to store stylesheets
     */
    public const CSS_DIR_NAME = 'css';

    /**
     * @param string|File $stimulusXmlSourceFile
     */
    public function store(
        $stimulusXmlSourceFile,
        string $stimulusFilename,
        array $cssFiles = []
    ): string {
        if ($stimulusXmlSourceFile instanceof File) {
            return $this->storeStream(
                $stimulusXmlSourceFile->readStream(),
                $stimulusFilename,
                $cssFiles
            );
        }

        return $this->storeStream(
            fopen($stimulusXmlSourceFile, 'r'),
            $stimulusFilename,
            $cssFiles
        );
    }

    public function getUniqueDirName(string $name): string
    {
        return $this->getUniqueName($name);
    }

    public function storeXmlStream($stimulusXmlStream, string $stimulusFilename, string $dirname): void
    {
        $fs = $this->getFileSystem();

        if (!$fs->directoryExists($dirname)) {
            $fs->createDirectory($dirname);
        }

        $fs->writeStream(
            $dirname . '/' . $stimulusFilename,
            $stimulusXmlStream
        );
    }

    /**
     * @param resource $stimulusXmlStream
     */
    public function storeStream(
        $stimulusXmlStream,
        string $stimulusFilename,
        array $cssFiles = []
    ): string {
        $dirname = $this->getUniqueName($stimulusFilename);
        $this->storeXmlStream($stimulusXmlStream, $stimulusFilename, $dirname);

        if (count($cssFiles)) {
            $fs = $this->getFileSystem();
            $fs->createDirectory($dirname . DIRECTORY_SEPARATOR . self::CSS_DIR_NAME);
            foreach ($cssFiles as $file) {
                if (!file_exists($file)) {
                    $this->getLogger()->notice(sprintf("file %s does not exist", $file));
                    continue;
                }

                if (!is_readable($file)) {
                    $this->getLogger()->notice(sprintf("file %s is not readable", $file));
                    continue;
                }

                $fs->writeStream(
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
