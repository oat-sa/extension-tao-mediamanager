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

namespace oat\taoMediaManager\model;

use oat\oatbox\user\User;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\PermissionChecker;
use oat\taoMediaManager\controller\MediaImport;
use oat\taoMediaManager\controller\MediaManager;
use core_kernel_classes_Resource as Resource;

class MediaPermissionsService
{
    /** @var ActionAccessControl */
    private $actionAccessControl;

    /** @var PermissionChecker */
    private $permissionChecker;

    public function __construct(ActionAccessControl $actionAcl, PermissionChecker $permissionChecker)
    {
        $this->actionAccessControl = $actionAcl;
        $this->permissionChecker = $permissionChecker;
    }

    public function isAllowedToImportMedia(User $user, Resource $resource): bool
    {
        if (!$this->isAllowedToEditResource($resource, $user)) {
            return false;
        }

        return $this->isAllowedToEditMedia();
    }

    public function isAllowedToReplaceMedia(bool $editAllowed): bool
    {
        return $editAllowed && $this->isAllowedToEditMedia();
    }

    public function isAllowedToEditResource(Resource $resource, User $user = null): bool
    {
        $editContext = new Context([
            Context::PARAM_CONTROLLER => MediaManager::class,
            Context::PARAM_ACTION => 'editInstance',
            Context::PARAM_USER => $user
        ]);

        return $this->permissionChecker->hasWriteAccess($resource->getUri(), $user)
            && $this->actionAccessControl->contextHasWriteAccess($editContext);
    }

    public function isAllowedToEditMedia(): bool
    {
        $editContext = new Context([
            Context::PARAM_CONTROLLER => MediaImport::class,
            Context::PARAM_ACTION => 'editMedia',
        ]);

        return $this->actionAccessControl->contextHasWriteAccess($editContext);
    }

    public function isAllowedToPreview(): bool
    {
        $previewContext = new Context([
            Context::PARAM_CONTROLLER => MediaManager::class,
            Context::PARAM_ACTION => 'isPreviewEnabled',
        ]);

        return $this->actionAccessControl->contextHasReadAccess($previewContext);
    }
}
