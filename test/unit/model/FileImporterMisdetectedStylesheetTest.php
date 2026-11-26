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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model;

use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\taoMediaManager\model\FileImporter;
use ReflectionMethod;

class FileImporterMisdetectedStylesheetTest extends TestCase
{
    /** @var ReflectionMethod */
    private $getMisdetectedStylesheetMime;

    public function setUp(): void
    {
        $this->getMisdetectedStylesheetMime = new ReflectionMethod(
            FileImporter::class,
            'getMisdetectedStylesheetMime'
        );
        $this->getMisdetectedStylesheetMime->setAccessible(true);
    }

    public function testCssFileDetectedAsHtmlIsReported(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('text/html');

        $this->assertSame('text/html', $this->detect($file, 'probe.css'));
    }

    public function testCssFileDetectedAsCssIsNotReported(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('text/css');

        $this->assertNull($this->detect($file, 'clean.css'));
    }

    public function testCssFileWithFailedDetectionIsReportedAsUnknown(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn(false);

        $this->assertSame('unknown', $this->detect($file, 'broken.css'));
    }

    public function testNonCssFileIsNotReported(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/png');

        $this->assertNull($this->detect($file, 'shot.png'));
    }

    /**
     * @param string|File $fileSource
     */
    private function detect($fileSource, string $fileName): ?string
    {
        return $this->getMisdetectedStylesheetMime->invoke(new FileImporter(), $fileSource, $fileName);
    }
}
