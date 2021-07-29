<?php

declare(strict_types=1);

namespace oat\taoMediaManager\model\mapper;

use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\media\mapper\MediaBrowserPermissionsMapper;
use taoItems_actions_ItemContent;

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

    protected function hasWriteAccess(string $uri): bool
    {
        return parent::hasWriteAccess($uri)
            && $this->getActionAccessControl()->hasWriteAccess(
                taoItems_actions_ItemContent::class,
                'files'
            );
    }

    private function getActionAccessControl(): ActionAccessControl
    {
        if (!isset($this->actionAccessControl)) {
            $this->actionAccessControl = $this->getServiceLocator()->get(ActionAccessControl::SERVICE_ID);
        }

        return $this->actionAccessControl;
    }
}
