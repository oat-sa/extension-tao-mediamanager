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

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\sharedStimulus\service\TempFileWriter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use RuntimeException;

class TempFileWriterTest  extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;

    public function setUp(): void
    {
        $this->vfsRoot = vfsStream::setup('TempFW');

        // Precondition: cache dir does not exist (it is created by the writer)
        //
        $this->assertFalse(file_exists($this->vfsRoot->path() . '/cache'));
        $this->assertFalse(file_exists($this->vfsRoot->path() . '/cache2'));
    }

    public function tearDown(): void
    {
        // Cache dirs should not be preserved across runs
        //
        if (is_dir($this->vfsRoot->path() . '/cache')) {
            rmdir($this->vfsRoot->path() . '/cache');
        }

        if (is_dir($this->vfsRoot->path() . '/cache2')) {
            rmdir($this->vfsRoot->path() . '/cache2');
        }
    }

    public function testWriteFile(): void
    {
        $sut = new TempFileWriter(
            $this->vfsRoot->path() . DIRECTORY_SEPARATOR . 'cache'
        );
        $path = $sut->writeFile('namespace', 'basename', 'data');

        // Paths are in the form namespace/RandomChars/basename
        //
        $this->assertEquals('namespace/', "{$this->getNamespaceFromPath($path)}/");
        $this->assertEquals('/basename', "/{$this->getBaseNameFromPath($path)}");

        $this->assertTrue(file_exists($path));
        $this->assertEquals('data', file_get_contents($path));

        $sut->removeTempFiles();
        $this->assertFalse(file_exists($this->vfsRoot->path() . '/cache'));
    }

    /**
     * @dependsOn testWriteFile
     */
    public function testRemoveTempFiles(): void
    {
        $sut = new TempFileWriter(
            $this->vfsRoot->path() . DIRECTORY_SEPARATOR . 'cache'
        );
        $path = $sut->writeFile('namespace', 'basename', 'data');
        $dir = dirname($path);
        $this->assertTrue(file_exists($path));
        $this->assertTrue(is_dir($dir));

        $sut->removeTempFiles();

        $this->assertFalse(file_exists($path));
        $this->assertFalse(file_exists($dir));
        $this->assertFalse(
            file_exists($this->vfsRoot->path() . DIRECTORY_SEPARATOR . 'cache')
        );
    }

    public function testWriteErrorThrowsException(): void
    {
        $sut = new TempFileWriter($this->vfsRoot->path() . '/cache');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error writing data to temp file');

        // Force a write error by using an invalid filename for the directory
        // and file ('.' is a reserved name).
        //
        $sut->writeFile('.', '.', 'data');
    }

    public function testCacheBaseDirIsCreatedRecursively(): void
    {
        $root = implode(
            DIRECTORY_SEPARATOR,
            [$this->vfsRoot->path() , 'cache2', 'nestedDir']
        );

        $sut = new TempFileWriter($root);
        $path = $sut->writeFile('namespace', 'basename', 'data');
        $prefix = $root . DIRECTORY_SEPARATOR . $this->getNamespaceFromPath($path);

        $this->assertTrue(is_dir($root));
        $this->assertTrue(is_dir($prefix));
        $this->assertTrue(is_file($path));

        $sut->removeTempFiles();

        $this->assertFalse(file_exists($path));
        $this->assertFalse(file_exists($prefix));
        $this->assertFalse(file_exists($root));
    }

    private function getNamespaceFromPath(string $path): string
    {
        return substr($this->getRelativePath($path), 0, 9);
    }

    private function getBaseNameFromPath(string $path): string
    {
        return substr($this->getRelativePath($path), -8);
    }

    private function getRelativePath(string $path): string
    {
        // Paths are in the form namespace/RandomChars/basename
        //
        return substr($path, strpos($path, 'namespace'));
    }
}
