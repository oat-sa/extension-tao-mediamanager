<?php

namespace test\unit\model;

use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\Context\ContextInterface;
use oat\taoMediaManager\controller\MediaManager;
use oat\taoMediaManager\model\MediaPermissionsService;
use Prophecy\Argument;

class MediaPermissionServiceTest extends TestCase
{
    private const RDF_ROOT = 'https://example.com/ontologies/tao.rdf';

    private const user1Id = self::RDF_ROOT.'#i6176cbaf9c8b998698d2bf8ac45d7f';

    private const user2Id = self::RDF_ROOT.'#i6176cbaf9c8b998698d2bf8ac00000';

    private const resource1Id = self::RDF_ROOT.'#i6176ce834db789e71df6a26d578625';

    private function getActionAccessControlMock(): ActionAccessControl
    {
        $actionAccessControlProphecy = $this->prophesize(ActionAccessControl::class);
        $actionAccessControlProphecy
            ->contextHasWriteAccess(Argument::that(function (ContextInterface $ctx) {
                return $ctx->getParameter(Context::PARAM_CONTROLLER) == MediaManager::class
                    && $ctx->getParameter(Context::PARAM_ACTION) == 'editInstance';
            }))
            ->willReturn(true);

        return $actionAccessControlProphecy->reveal();
    }

    private function getPermissionCheckerMock(string $userId): PermissionChecker
    {
        $isTestUserPredicate = function (?User $user) use ($userId) {
            return null !== $user && $user->getIdentifier() == $userId;
        };

        $isNotTestUserPredicate = function (?User $user) use ($userId) {
            return null !== $user && $user->getIdentifier() != $userId;
        };

        $permissionCheckerProphecy = $this->prophesize(PermissionChecker::class);
        $permissionCheckerProphecy
            ->hasWriteAccess(Argument::is(self::resource1Id), Argument::that($isTestUserPredicate))
            ->willReturn(true);
        $permissionCheckerProphecy
            ->hasWriteAccess(Argument::is(self::resource1Id), Argument::that($isNotTestUserPredicate))
            ->willReturn(false);
        $permissionCheckerProphecy
            ->hasWriteAccess(Argument::not(self::resource1Id), Argument::any())
            ->willReturn(false);

        return $permissionCheckerProphecy->reveal();
    }

    public function testIsAllowedToEditResource(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl = $this->getActionAccessControlMock();

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker grants write access for a known user
        //
        $resourceMock = $this->createMock(\core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants editInstance access
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user1Id);

        // THEN MediaPermissionsService allows that user to edit the resource
        //
        $this->assertTrue($service->isAllowedToEditResource($resourceMock, $user1));
    }

    public function testIsNotAllowedToEditResource(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl = $this->getActionAccessControlMock();

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker DOESN'T grant write access for a known user
        //
        $resourceMock = $this->createMock(\core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants editInstance access
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user2Id);

        // THEN MediaPermissionsService does not allow that user to edit the resource
        //
        $this->assertFalse($service->isAllowedToEditResource($resourceMock, $user1));
    }
}
