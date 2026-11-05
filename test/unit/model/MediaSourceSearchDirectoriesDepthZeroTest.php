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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model;

use core_kernel_classes_Class;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\mapper\MediaSourcePermissionsMapper;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\TaoMediaOntology;
use ReflectionMethod;
use ReflectionProperty;
use tao_helpers_Uri;

class MediaSourceSearchDirectoriesDepthZeroTest extends TestCase
{
    private const FOLDER_CLASS = 'http://example.test#FolderA';

    public function testDepthZeroEmptyRootKeepsClassPathAndEmptyParent(): void
    {
        $class = $this->createClassStub(TaoMediaOntology::CLASS_URI_MEDIA_ROOT, 'Assets');
        $subject = $this->createSearchDirectoriesSubject($class);

        $result = $this->invokeSearchDirectories($subject, '');

        $expectedPath = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);
        $this->assertSame($expectedPath, $result['path']);
        $this->assertSame('', $result['parent']);
    }

    public function testDepthZeroTaomediaScopeUsesResolvedClassUriForPathAndParent(): void
    {
        $class = $this->createClassStub(self::FOLDER_CLASS, 'Folder A');
        $subject = $this->createSearchDirectoriesSubject($class);
        $parentLink = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(self::FOLDER_CLASS);

        $result = $this->invokeSearchDirectories($subject, $parentLink);

        $expectedPath = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(self::FOLDER_CLASS);
        $this->assertSame($expectedPath, $result['path']);
        $this->assertSame(self::FOLDER_CLASS, $result['parent']);
        $this->assertNotSame(
            MediaSource::SCHEME_NAME . tao_helpers_Uri::encode($parentLink),
            $result['path']
        );
    }

    private function createClassStub(string $uri, string $label): core_kernel_classes_Class
    {
        $class = $this->createMock(core_kernel_classes_Class::class);
        $class->method('getUri')->willReturn($uri);
        $class->method('getLabel')->willReturn($label);
        $class->method('getParentClasses')->willReturn([]);

        return $class;
    }

    /**
     * @return MediaSource&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSearchDirectoriesSubject(core_kernel_classes_Class $class): MediaSource
    {
        $mapper = $this->createMock(MediaSourcePermissionsMapper::class);
        $mapper->method('map')->willReturnCallback(static fn (array $payload): array => $payload);

        $subject = $this->getMockBuilder(MediaSource::class)
            ->onlyMethods(['getClass'])
            ->getMock();
        $subject->method('getClass')->willReturn($class);

        $mapperProperty = new ReflectionProperty(MediaSource::class, 'permissionsMapper');
        $mapperProperty->setAccessible(true);
        $mapperProperty->setValue($subject, $mapper);

        return $subject;
    }

    /**
     * @return array<string, mixed>
     */
    private function invokeSearchDirectories(MediaSource $subject, string $parentLink): array
    {
        $method = new ReflectionMethod(MediaSource::class, 'searchDirectories');
        $method->setAccessible(true);

        /** @var array<string, mixed> $result */
        $result = $method->invoke($subject, $parentLink, [], 0, 0, 0, null);

        return $result;
    }
}
