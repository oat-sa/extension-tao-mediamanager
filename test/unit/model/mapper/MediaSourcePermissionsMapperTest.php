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

namespace oat\taoMediaManager\test\unit\model\mapper;

use oat\generis\test\TestCase;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\taoMediaManager\model\mapper\MediaSourcePermissionsMapper;
use PHPUnit\Framework\MockObject\MockObject;

class MediaSourcePermissionsMapperTest extends TestCase
{
    /** @var ActionAccessControl|MockObject */
    private $actionAccessControl;

    /** @var MediaSourcePermissionsMapper */
    private $subject;

    /** @var PermissionChecker|MockObject */
    private $permissionChecker;

    public function setUp(): void
    {
        $this->actionAccessControl = $this->createMock(ActionAccessControl::class);
        $this->permissionChecker = $this->createMock(PermissionChecker::class);
        $this->subject = new MediaSourcePermissionsMapper();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ActionAccessControl::SERVICE_ID => $this->actionAccessControl,
                    PermissionChecker::class => $this->permissionChecker,
                ]
            )
        );
    }

    public function testMapWithAllPermissions(): void
    {
        $data = [];
        $resourceUri = 'resourceUri';

        $this->actionAccessControl
            ->method('contextHasReadAccess')
            ->willReturn(true);

        $this->actionAccessControl
            ->method('hasReadAccess')
            ->willReturn(true);

        $this->actionAccessControl
            ->method('contextHasWriteAccess')
            ->willReturn(true);

        $this->actionAccessControl
            ->method('hasWriteAccess')
            ->willReturn(true);

        $this->assertEquals(
            [
                'permissions' => [
                    'READ',
                    'WRITE',
                    'DOWNLOAD',
                    'DELETE',
                    'UPLOAD',
                ]
            ],
            $this->subject->map($data, $resourceUri)
        );
    }

    public function testMapWithOnlyReadAndWritePermissions(): void
    {
        $data = [];
        $resourceUri = 'resourceUri';

        $this->actionAccessControl
            ->method('contextHasReadAccess')
            ->willReturn(false);

        $this->actionAccessControl
            ->method('hasReadAccess')
            ->willReturn(true);

        $this->actionAccessControl
            ->method('contextHasWriteAccess')
            ->willReturn(false);

        $this->actionAccessControl
            ->method('hasWriteAccess')
            ->willReturn(true);

        $this->assertEquals(
            [
                'permissions' => [
                    'READ',
                    'WRITE',
                ]
            ],
            $this->subject->map($data, $resourceUri)
        );
    }
}
