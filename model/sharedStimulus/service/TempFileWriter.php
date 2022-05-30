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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

namespace oat\taoMediaManager\model\sharedStimulus\service;

use RuntimeException;

/**
 * @todo Unit tests
 */
class TempFileWriter
{
    /** @var ?string */
    private $cacheDirectory;

    /** @var string[] */
    private $createdFiles = [];

    /** @var string[] */
    private $createdDirectories = [];

    private $logger; //@fixme To be removed

    public function __construct(string $cacheDirectory = null)
    {
        $this->logger = \common_Logger::singleton();
        $this->logger->logError("Created instance of TemFileWriter");

        $this->cacheDirectory = $cacheDirectory;

        if (null === $this->cacheDirectory) {
            $this->cacheDirectory = defined('GENERIS_CACHE_PATH')
                ? GENERIS_CACHE_PATH
                : sys_get_temp_dir();
        }
    }

    public function __destruct()
    {
        $this->removeTempFiles();
    }

    public function removeTempFiles(): void
    {
        foreach ($this->createdFiles as $path) {
            $this->logger->logError("Removing file " . $path);
            if (!unlink($path)) {
                throw new \Exception("wtf");
            }
        }

        foreach ($this->createdDirectories as $path) {
            \common_Logger::singleton()->logInfo("Removing file " . $path);
            unlink($path);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function writeFile(
        string $namespace,
        string $basename,
        string $data
    ): string {
        $path = $this->createTempDirectory($namespace)
            . DIRECTORY_SEPARATOR
            . $basename;

        if (!file_put_contents($path, $data)) {
            throw new RuntimeException('Error writing data to temp file');
        }

        \common_Logger::singleton()->logError("Written file " . $path);

        $this->createdFiles[] = $path;

        return $path;
    }

    /**
     * @throws RuntimeException
     */
    private function createTempDirectory(string $namespace): string
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }

        $cacheDir = $this->cacheDirectory . DIRECTORY_SEPARATOR . $namespace;

        if (!is_dir($cacheDir)) {
            $this->createdDirectories[] = $cacheDir;
            mkdir($cacheDir);
        }

        $tmpDir = tempnam($cacheDir, null);

        if (!unlink($tmpDir) || !mkdir($tmpDir)) {
            throw new RuntimeException(
                "Unable to create temp directory {$tmpDir}"
            );
        }

        \common_Logger::singleton()->logError(
            "Adding created dir: {$tmpDir}"
        );

        $this->createdDirectories[] = $tmpDir;

        return $cacheDir;
    }
}
