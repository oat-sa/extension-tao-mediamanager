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

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\TaoMediaOntology;
use tao_helpers_Uri;

class MediaSourceResolveScopeClassUriTest extends TestCase
{
    private const FOLDER_CLASS = 'http://example.test#FolderA';

    /** @var MediaSource */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(MediaSource::class)
            ->onlyMethods(['getRootClassUri'])
            ->getMock();
        $this->subject->method('getRootClassUri')->willReturn(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);
    }

    public function testEmptyOrSlashParentResolvesToMediaRoot(): void
    {
        $this->assertSame(TaoMediaOntology::CLASS_URI_MEDIA_ROOT, $this->subject->resolveScopeClassUri(''));
        $this->assertSame(TaoMediaOntology::CLASS_URI_MEDIA_ROOT, $this->subject->resolveScopeClassUri('/'));
    }

    public function testTaomediaBrowsePathResolvesToDecodedClass(): void
    {
        $link = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(self::FOLDER_CLASS);

        $this->assertSame(self::FOLDER_CLASS, $this->subject->resolveScopeClassUri($link));
    }

    public function testTaomediaRootPathResolvesToMediaRoot(): void
    {
        $link = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(TaoMediaOntology::CLASS_URI_MEDIA_ROOT);

        $this->assertSame(TaoMediaOntology::CLASS_URI_MEDIA_ROOT, $this->subject->resolveScopeClassUri($link));
    }

    public function testHttpClassUriPassThrough(): void
    {
        $this->assertSame(self::FOLDER_CLASS, $this->subject->resolveScopeClassUri(self::FOLDER_CLASS));
    }

    public function testEncodedBrowseIdentifierWithoutSchemeResolvesToClass(): void
    {
        $encoded = tao_helpers_Uri::encode(self::FOLDER_CLASS);

        $this->assertSame(self::FOLDER_CLASS, $this->subject->resolveScopeClassUri($encoded));
    }
}
