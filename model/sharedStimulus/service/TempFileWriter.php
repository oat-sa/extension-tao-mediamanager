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

class TempFileWriter
{
    /** @var string */
    private $cacheDirectory;

    /** @var string[] */
    private $createdFiles = [];

    /** @var string[] */
    private $createdDirectories = [];

    public function __construct(string $cacheDirectory = null)
    {
        $this->cacheDirectory = $cacheDirectory;

        if (null === $this->cacheDirectory) {
            $this->cacheDirectory = defined('GENERIS_CACHE_PATH') && !empty(GENERIS_CACHE_PATH)
                ? GENERIS_CACHE_PATH
                : sys_get_temp_dir();
        }

        $this->createRootCacheDirectory();
        $this->cacheDirectory = realpath($this->cacheDirectory);
    }

    public function __destruct()
    {
        $this->removeTempFiles();
    }

    public function removeTempFiles(): void
    {
        foreach (array_reverse($this->createdFiles) as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        foreach (array_reverse($this->createdDirectories) as $path) {
            if (is_dir($path)) {
                rmdir($path);
            }
        }

        $this->createdFiles = [];
        $this->createdDirectories = [];
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

        if (!@file_put_contents($path, $data)) {
            throw new RuntimeException(
                'Error writing data to temp file: '.
                error_get_last()['message']
            );
        }

        $path = realpath($path);
        $this->createdFiles[] = $path;

        return $path;
    }

    /**
     * @throws RuntimeException
     */
    private function createTempDirectory(string $namespace): string
    {
        $this->createRootCacheDirectory();

        $cacheDir = $this->cacheDirectory . DIRECTORY_SEPARATOR . $namespace;

        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0777, true)) {
                throw new RuntimeException(
                    'Cannot create subdirectory under temp directory'
                );
            }

            $this->createdDirectories[] = realpath($cacheDir);
        }

        $tmpDir = realpath($cacheDir) . DIRECTORY_SEPARATOR . uniqid();
        if (!mkdir($tmpDir)) {
            throw new RuntimeException(
                "Unable to create temp directory {$tmpDir}"
            );
        }

        $this->createdDirectories[] = $tmpDir;

        return $tmpDir;
    }

    private function createRootCacheDirectory(): void
    {
        if (!is_dir($this->cacheDirectory)) {
            if (!mkdir($this->cacheDirectory, 0777, true)) {
                throw new RuntimeException(
                    'Cache root directory does not exist and cannot be created'
                );
            }

            $this->createdDirectories[] = realpath($this->cacheDirectory);
        }
    }
}
