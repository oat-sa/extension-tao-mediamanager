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

namespace oat\taoMediaManager\test\unit\model;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\MediaService;

class MediaServiceTest extends TestCase
{
    /** @var MediaService */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new MediaService();
    }

    /**
     * @dataProvider isXmlAllowedMimeTypeDataProvider
     */
    public function testIsXmlAllowedMimeType(bool $expected, string $type): void
    {
        $this->assertEquals($expected, $this->sut->isXmlAllowedMimeType($type));
    }

    public function isXmlAllowedMimeTypeDataProvider(): array
    {
        return [
            'Empty string is not an allowed type' => [
                'expected' => false,
                'type' => ''
            ],
            'application/xml is an allowed type' => [
                'expected' => true,
                'type' => 'application/xml'
            ],
            'shared stimulus MIME type is an allowed type' => [
                'expected' => true,
                'type' => MediaService::SHARED_STIMULUS_MIME_TYPE
            ],
            'A string containing only whitespaces is not an allowed type' => [
                'expected' => false,
                'type' => ' '
            ],
            'application/json is not an allowed type' => [
                'expected' => false,
                'type' => 'application/json'
            ],
            'text/json is not an allowed type' => [
                'expected' => false,
                'type' => 'application/json'
            ],
            'application/xml;charset=utf-8 is an allowed type' => [
                'expected' => true,
                'type' => 'application/xml;charset=utf-8'
            ],
            'application/xml;property=value is an allowed type' => [
                'expected' => true,
                'type' => 'application/xml;property=value'
            ],
            'application/json;charset=utf-8 is not an allowed type' => [
                'expected' => false,
                'type' => 'application/json;property=value'
            ],
            'application/json;property=value is not an allowed type' => [
                'expected' => false,
                'type' => 'application/json;property=value'
            ],
        ];
    }
}
