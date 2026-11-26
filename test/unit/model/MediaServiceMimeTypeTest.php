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
use oat\taoMediaManager\model\MediaService;
use ReflectionMethod;

class MediaServiceMimeTypeTest extends TestCase
{
    /** @var MediaService */
    private $sut;

    /** @var ReflectionMethod */
    private $resolveMimeType;

    public function setUp(): void
    {
        $this->sut = new MediaService();
        $this->sut->setLogger(new \Psr\Log\NullLogger());
        $this->resolveMimeType = new ReflectionMethod(MediaService::class, 'resolveMimeType');
        $this->resolveMimeType->setAccessible(true);
    }

    public function testCssFileDetectedAsHtmlIsResolvedAsCss(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('text/html');
        $file->method('getBasename')->willReturn('probe-red.css');

        $this->assertSame('text/css', $this->resolve($file, null));
    }

    public function testPlainTextCssFileIsResolvedAsCss(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('text/plain');
        $file->method('getBasename')->willReturn('green.css');

        $this->assertSame('text/css', $this->resolve($file, null));
    }

    public function testNonCssFileKeepsDetectedMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/png');
        $file->method('getBasename')->willReturn('shot.png');

        $this->assertSame('image/png', $this->resolve($file, null));
    }

    public function testCssFileWithFailedDetectionIsResolvedAsCss(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn(false);
        $file->method('getBasename')->willReturn('broken.css');

        $this->assertSame('text/css', $this->resolve($file, null));
    }

    public function testCssPathStringIsResolvedAsCss(): void
    {
        $path = $this->createTempFile('css');

        try {
            $this->assertSame('text/css', $this->resolve($path, null));
        } finally {
            unlink($path);
        }
    }

    public function testPngPathStringKeepsDetectedMimeType(): void
    {
        $path = $this->createTempFile('png');

        try {
            $this->assertSame('image/png', $this->resolve($path, null));
        } finally {
            unlink($path);
        }
    }

    public function testCssFileDetectedAsHtmlLogsWarningAndIsResolvedAsCss(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('probe-red.css'));
        $this->sut->setLogger($logger);

        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('text/html');
        $file->method('getBasename')->willReturn('probe-red.css');

        $this->assertSame('text/css', $this->resolve($file, null));
    }

    public function testCssFileDetectedAsCssDoesNotLogWarning(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $this->sut->setLogger($logger);

        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('text/css');
        $file->method('getBasename')->willReturn('clean.css');

        $this->assertSame('text/css', $this->resolve($file, null));
    }

    public function testNonCssFileDoesNotLogWarning(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $this->sut->setLogger($logger);

        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/png');
        $file->method('getBasename')->willReturn('shot.png');

        $this->assertSame('image/png', $this->resolve($file, null));
    }

    /**
     * @param string|File $fileSource
     */
    private function resolve($fileSource, ?string $label): string
    {
        return $this->resolveMimeType->invoke($this->sut, $fileSource, $label);
    }

    private function createTempFile(string $extension): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'mms33');
        $path = $temporaryPath . '.' . $extension;
        rename($temporaryPath, $path);

        if ($extension === 'png') {
            file_put_contents(
                $path,
                base64_decode(
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
                )
            );
        }

        return $path;
    }
}
