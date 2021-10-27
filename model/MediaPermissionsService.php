<?php

namespace oat\taoMediaManager\model;

use oat\oatbox\user\User;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\Context\ContextInterface;
use oat\taoMediaManager\controller\MediaImport;
use oat\taoMediaManager\controller\MediaManager;
use core_kernel_classes_Resource;

class MediaPermissionsService
{
    /** @var ActionAccessControl */
    private $actionAcl;

    /** @var PermissionChecker */
    private $permissionChecker;

    public function __construct(
        ActionAccessControl $actionAcl,
        PermissionChecker $permissionChecker
    ) {
        $this->actionAcl = $actionAcl;
        $this->permissionChecker = $permissionChecker;
    }

    public function isAllowedToReplaceMedia(bool $editAllowed): bool
    {
        return $editAllowed && $this->isAllowedToEditMedia();
    }

    public function isAllowedToEdit(User $user, core_kernel_classes_Resource $resource): bool
    {
        $editContext = new Context([
            Context::PARAM_CONTROLLER => MediaManager::class,
            Context::PARAM_ACTION => 'editInstance',
            Context::PARAM_USER => $user
        ]);

        return $this->hasWriteAccess($resource->getUri())
            && $this->hasWriteAccessByContext($editContext);
    }

    public function isAllowedToEditMedia(): bool
    {
        $editContext = new Context([
            Context::PARAM_CONTROLLER => MediaImport::class,
            Context::PARAM_ACTION => 'editMedia',
        ]);

        return $this->hasWriteAccessByContext($editContext);
    }

    public function isAllowedToPreview(): bool
    {
        $previewContext = new Context([
            Context::PARAM_CONTROLLER => MediaManager::class,
            Context::PARAM_ACTION => 'isPreviewEnabled',
        ]);

        return $this->hasReadAccessByContext($previewContext);
    }

    // Helpers

    protected function hasReadAccessByContext(ContextInterface $context): bool
    {
        return $this->actionAcl->contextHasReadAccess($context);
    }

    protected function hasWriteAccessByContext(ContextInterface $context): bool
    {
        return $this->actionAcl->contextHasWriteAccess($context);
    }

    /**
     * Test whenever the current user has "WRITE" access to the specified id
     *
     * @param string $resourceId
     * @return boolean
     */
    private function hasWriteAccess(string $resourceId): bool
    {
        return $this->permissionChecker->hasWriteAccess($resourceId);
    }
}
