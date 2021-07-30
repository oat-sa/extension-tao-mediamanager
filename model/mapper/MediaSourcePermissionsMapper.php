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
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\media\mapper\MediaBrowserPermissionsMapper;

class MediaSourcePermissionsMapper extends MediaBrowserPermissionsMapper
{
    /** @var ActionAccessControl */
    private $actionAccessControl;

    protected function hasReadAccess(string $uri): bool
    {
        return parent::hasReadAccess($uri)
            && $this->getActionAccessControl()->hasReadAccess(
                taoItems_actions_ItemContent::class,
                'files'
            );
    }

    /*
     *  TODO split permissions to upload and delete assets
     */
    protected function hasWriteAccess(string $uri): bool
    {
        $canDelete = $this->getActionAccessControl()->hasWriteAccess(
            taoItems_actions_ItemContent::class,
            'delete'
        );
        $canUpload = $this->getActionAccessControl()->hasWriteAccess(
            taoItems_actions_ItemContent::class,
            'upload'
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
}
