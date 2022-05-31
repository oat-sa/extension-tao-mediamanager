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

class TempFileWriterTest  extends TestCase
{
    /** @var TempFileWriter */
    private $sut;

    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;

    public function setUp(): void
    {
        $this->vfsRoot = vfsStream::setup('TempFW');

        $this->sut = new TempFileWriter($this->vfsRoot->path());
    }

    public function testWriteFile(): void
    {
        $path = $this->sut->writeFile('namespace', 'basename', 'data');

        // Paths are something like namespace/RandomChars/basename
        //
        $relative = substr($path, strpos($path, 'namespace'));
        $this->assertEquals('namespace/', substr($relative, 0, 10));
        $this->assertEquals('/basename', substr($relative, -9));

        $this->assertTrue(file_exists($path));
        $this->assertEquals('data', file_get_contents($path));
    }

    public function testRemoveTempFiles(): void
    {
        $this->markTestIncomplete('Not implemented');
    }

    public function testTempFilesRemovedOnDestructorCall(): void
    {
        $this->markTestIncomplete('Not implemented');
    }
}
