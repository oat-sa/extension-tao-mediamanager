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

namespace oat\taoMediaManager\test\unit\model\accessControl;

use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\PermissionChecker;
use oat\taoMediaManager\controller\MediaManager;
use oat\taoMediaManager\model\accessControl\MediaPermissionService;
use PHPUnit\Framework\MockObject\MockObject;

class MediaPermissionServiceTest extends TestCase
{
    /** @var ActionAccessControl|MockObject */
    private $actionAccessControl;

    /** @var PermissionChecker|MockObject */
    private $permissionChecker;

    /** @var MediaPermissionService */
    private $sut;

    public function setUp(): void
    {
        $this->actionAccessControl = $this->createMock(ActionAccessControl::class);
        $this->permissionChecker = $this->createMock(PermissionChecker::class);
        $this->sut = new MediaPermissionService($this->actionAccessControl, $this->permissionChecker);
    }

    /**
     * @dataProvider isAllowedToEditResourceDataProvider
     */
    public function testIsAllowedToEditResource(bool $expected, bool $hasWriteAccess, bool $contextHasWriteAccess): void
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);
        $user = $this->createMock(User::class);

        $this->permissionChecker
            ->method('hasWriteAccess')
            ->willReturn($hasWriteAccess);

        $this->actionAccessControl
            ->method('contextHasWriteAccess')
            ->willReturn($contextHasWriteAccess);

        self::assertSame($expected, $this->sut->isAllowedToEditResource($resource, $user));
    }

    public function isAllowedToEditResourceDataProvider(): array
    {
        return [
            'Has write access to resource and context' => [
                'expected' => true,
                'hasWriteAccess' => true,
                'contextHasWriteAccess' => true,
            ],
            'Has no write access to resource' => [
                'expected' => false,
                'hasWriteAccess' => false,
                'contextHasWriteAccess' => true,
            ],
            'Has no write access to context' => [
                'expected' => false,
                'hasWriteAccess' => true,
                'contextHasWriteAccess' => false,
            ],
            'Has no write access to resource nor context' => [
                'expected' => false,
                'hasWriteAccess' => true,
                'contextHasWriteAccess' => false,
            ],
        ];
    }

    /**
     * @dataProvider isAllowedToEditMediaDataProvider
     */
    public function testIsAllowedToEditMedia(bool $expected, bool $contextHasWriteAccess): void
    {
        $user = $this->createMock(User::class);

        $this->actionAccessControl
            ->method('contextHasWriteAccess')
            ->willReturn($contextHasWriteAccess);

        self::assertSame($expected, $this->sut->isAllowedToEditMedia($user));
    }

    public function isAllowedToEditMediaDataProvider(): array
    {
        return [
            'Has write access to context' => [
                'expected' => true,
                'contextHasWriteAccess' => true,
            ],
            'Has no write access to context' => [
                'expected' => false,
                'contextHasWriteAccess' => false,
            ],
        ];
    }

    /**
     * @dataProvider isAllowedToPreviewDataProvider
     */
    public function testIsAllowedToPreview(bool $expected, bool $contextHasReadAccess): void
    {
        $user = $this->createMock(User::class);

        $this->actionAccessControl
            ->method('contextHasReadAccess')
            ->with(
                new Context([
                    Context::PARAM_CONTROLLER => MediaManager::class,
                    Context::PARAM_ACTION => 'isPreviewEnabled',
                    Context::PARAM_USER => $user
                ])
            )->willReturn($contextHasReadAccess);

        self::assertSame($expected, $this->sut->isAllowedToPreview($user));
    }

    public function isAllowedToPreviewDataProvider(): array
    {
        return [
            'Has read access to context' => [
                'expected' => false,
                'contextHasReadAccess' => false,
            ],
            'Has no read access to context' => [
                'expected' => false,
                'contextHasReadAccess' => false,
            ],
        ];
    }
}
