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

namespace oat\taoMediaManager\model\mapper;

use taoItems_actions_ItemContent;
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\media\mapper\MediaBrowserPermissionsMapper;

class MediaSourcePermissionsMapper extends MediaBrowserPermissionsMapper
{
    private const PERMISSION_PREVIEW = 'PREVIEW';
    private const PERMISSION_DOWNLOAD = 'DOWNLOAD';
    private const PERMISSION_UPLOAD = 'UPLOAD';
    private const PERMISSION_DELETE = 'DELETE';

    /** @var ActionAccessControl */
    private $actionAccessControl;

    public function map(array $data, string $resourceUri): array
    {
        $data = parent::map($data, $resourceUri);
        $hasReadAccess = $this->hasReadAccess($resourceUri);

        if (
            $this->hasReadAccessByContext(taoItems_actions_ItemContent::class, 'previewAsset')
            && $hasReadAccess
        ) {
            $data[self::DATA_PERMISSIONS][] = self::PERMISSION_PREVIEW;
        }

        if (
            $this->hasReadAccessByContext(taoItems_actions_ItemContent::class, 'downloadAsset')
            && $hasReadAccess
        ) {
            $data[self::DATA_PERMISSIONS][] = self::PERMISSION_DOWNLOAD;
        }

        $hasWriteAccess = $this->hasWriteAccess($resourceUri);

        if (
            $this->hasWriteAccessByContext(taoItems_actions_ItemContent::class, 'deleteAsset')
            && $hasWriteAccess
        ) {
            $data[self::DATA_PERMISSIONS][] = self::PERMISSION_DELETE;
        }

        if (
            $this->hasWriteAccessByContext(taoItems_actions_ItemContent::class, 'uploadAsset')
            && $hasWriteAccess
        ) {
            $data[self::DATA_PERMISSIONS][] = self::PERMISSION_UPLOAD;
        }

        return $data;
    }

    protected function hasReadAccess(string $uri): bool
    {
        return parent::hasReadAccess($uri)
            && $this->hasReadAccessByContext(taoItems_actions_ItemContent::class, 'viewAsset');
    }

    protected function hasWriteAccess(string $uri): bool
    {
        $canDelete = $this->hasWriteAccessByContext(
            taoItems_actions_ItemContent::class,
            'deleteAsset'
        );
        $canUpload = $this->hasWriteAccessByContext(
            taoItems_actions_ItemContent::class,
            'uploadAsset'
        );

        return parent::hasWriteAccess($uri) && ($canDelete || $canUpload);
    }

    private function getActionAccessControl(): ActionAccessControl
    {
        if (!isset($this->actionAccessControl)) {
            $this->actionAccessControl = $this->getServiceLocator()->get(ActionAccessControl::SERVICE_ID);
        }

        return $this->actionAccessControl;
    }

    private function hasReadAccessByContext(string $controller, string $action): bool
    {
        return $this->getActionAccessControl()->contextHasReadAccess(
            new Context([
                Context::PARAM_CONTROLLER => $controller,
                Context::PARAM_ACTION => $action,
            ])
        );
    }

    private function hasWriteAccessByContext(string $controller, string $action): bool
    {
        return $this->getActionAccessControl()->contextHasWriteAccess(
            new Context([
                Context::PARAM_CONTROLLER => $controller,
                Context::PARAM_ACTION => $action,
            ])
        );
    }
}
