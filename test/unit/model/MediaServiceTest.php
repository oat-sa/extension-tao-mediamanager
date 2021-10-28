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
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\taoMediaManager\model\mapper\MediaSourcePermissionsMapper;
use oat\taoMediaManager\model\MediaService;
use PHPUnit\Framework\MockObject\MockObject;

class MediaServiceTest extends TestCase
{
    public function testNullIsNotAnAllowedType(): void
    {
        $data = null;
        $this->assertFalse((new MediaService())->isXmlAllowedMimeType($data));
    }

    public function testEmptyStringIsNotAnAllowedType(): void
    {
        $this->assertFalse((new MediaService())->isXmlAllowedMimeType(''));
    }

    public function testXmlMimeTypeIsAnAllowedType(): void
    {
        $this->assertTrue((new MediaService())->isXmlAllowedMimeType('application/xml'));
    }

    public function testSharedStimulusMimeTypeIsAnAllowedType(): void
    {
        $this->assertTrue((new MediaService())->isXmlAllowedMimeType(MediaService::SHARED_STIMULUS_MIME_TYPE));
    }
}
