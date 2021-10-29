<?php

namespace test\unit\model;

use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\Context;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\Context\AbstractContext;
use oat\tao\model\Context\ContextInterface;
use oat\taoMediaManager\controller\MediaImport;
use oat\taoMediaManager\controller\MediaManager;
use oat\taoMediaManager\model\MediaPermissionsService;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;

class MediaPermissionServiceTest extends TestCase
{
    private const RDF_ROOT = 'https://example.com/ontologies/tao.rdf';

    private const user1Id = self::RDF_ROOT . '#i6176cbaf9c8b998698d2bf8ac45d7f';

    private const user2Id = self::RDF_ROOT.'#i6176cbaf9c8b998698d2bf8ac00000';

    private const resource1Id = self::RDF_ROOT.'#i6176ce834db789e71df6a26d578625';

    /**
     * @var ActionAccessControl|MockObject
     */
    private $actionAccessControl;

    /** @var PermissionChecker|MockObject */
    private $permissionChecker;

    /** @var MediaPermissionsService */
    private $sut;

    public function setUp(): void
    {
        $this->actionAccessControl = $this->createMock(ActionAccessControl::class);
        $this->permissionChecker = $this->createMock(PermissionChecker::class);
        $this->sut = new MediaPermissionsService($this->actionAccessControl, $this->permissionChecker);
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
            'Has write access' => [
                'expected' => true,
                'hasWriteAccess' => true,
                'contextHasWriteAccess' => true,
            ],
            'Has no write access' => [
                'expected' => false,
                'hasWriteAccess' => false,
                'contextHasWriteAccess' => true,
            ],
            'Has no context write access' => [
                'expected' => false,
                'hasWriteAccess' => true,
                'contextHasWriteAccess' => false,
            ],
        ];
    }

    public function isAllowedToEditMedia(): void
    {
        $user = $this->createMock(User::class);

        $this->actionAccessControl
            ->method('contextHasWriteAccess')
            ->with(
                new Context([
                    Context::PARAM_CONTROLLER => MediaImport::class,
                    Context::PARAM_ACTION => 'editMedia',
                    Context::PARAM_USER => $user
                ])
            )->willReturn(true);

        self::assertTrue($this->sut->isAllowedToEditMedia($user));
    }

    public function isAllowedToPreview(): void
    {
        $user = $this->createMock(User::class);

        $this->actionAccessControl
            ->method('contextHasWriteAccess')
            ->with(
                new Context([
                    Context::PARAM_CONTROLLER => MediaManager::class,
                    Context::PARAM_ACTION => 'isPreviewEnabled',
                    Context::PARAM_USER => $user
                ])
            )->willReturn(true);

        self::assertTrue($this->sut->isAllowedToPreview($user));
    }

    //FIXME ================

    private function getActionAccessControlMock(
        string $controller,
        string $action,
        bool $write,
        string $userId = null
    ): ActionAccessControl {
        $constraint = function (ContextInterface $ctx) use($controller, $action, $userId) {
            if(null !== $userId) {
                return $ctx->getParameter(Context::PARAM_CONTROLLER) == $controller
                    && $ctx->getParameter(Context::PARAM_ACTION) == $action
                    && $ctx->getParameter(Context::PARAM_USER) !== null
                    && $ctx->getParameter(Context::PARAM_USER)->getIdentifier() == $userId;
            } else {
                return $ctx->getParameter(Context::PARAM_CONTROLLER) == $controller
                    && $ctx->getParameter(Context::PARAM_ACTION) == $action;
            }
        };

        $opposite = function (ContextInterface $ctx) use ($constraint) {
            return !$constraint($ctx);
        };

        $actionAccessControlProphecy = $this->prophesize(ActionAccessControl::class);

        if ($write) {
            $actionAccessControlProphecy
                ->contextHasWriteAccess(Argument::that($constraint))
                ->willReturn(true);

            $actionAccessControlProphecy
                ->contextHasWriteAccess(Argument::that($opposite))
                ->willReturn(false);
        } else {
            $actionAccessControlProphecy
                ->contextHasReadAccess(Argument::that($constraint))
                ->willReturn(true);

            $actionAccessControlProphecy
                ->contextHasReadAccess(Argument::that($opposite))
                ->willReturn(false);

            $actionAccessControlProphecy
                ->contextHasWriteAccess(Argument::any())
                ->willReturn(false);
        }

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

    public function testIsAllowedToEditResource2(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl = $this->getActionAccessControlMock(MediaManager::class, 'editInstance', true);

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker grants write access for a known user
        //
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
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
        $actionAccessControl = $this->getActionAccessControlMock(MediaManager::class, 'editInstance', true);

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker DOESN'T grant write access for a known user
        //
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants editInstance access
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user2Id);

        // THEN MediaPermissionsService does not allow that user to edit the resource
        //
        $this->assertFalse($service->isAllowedToEditResource($resourceMock, $user1));
    }

    public function testIsAllowedToEditMedia(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl
            = $this->getActionAccessControlMock(MediaImport::class, 'editMedia', true, self::user1Id);

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker grants write access for a known user
        //
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants editMedia access
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user1Id);

        // THEN MediaPermissionsService does allow that user to edit the resource
        //
        $this->assertTrue($service->isAllowedToEditMedia($user1));


        // AND GIVEN a user for which ACL does NOT grant editMedia access
        //
        $user2 = $this->createMock(User::class);
        $user2->method('getIdentifier')->willReturn(self::user2Id);

        // THEN MediaPermissionsService does NOT allow that user to edit the resource
        //
        $this->assertFalse($service->isAllowedToEditMedia($user2));
    }

    public function testIsAllowedToEditMediaWithDifferentGrant(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl
            = $this->getActionAccessControlMock(MediaImport::class, 'otherPerm', true, self::user1Id);

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker grants write access for a known user
        //
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants access for an action other than editMedia
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user1Id);

        // THEN MediaPermissionsService does NOT  allow that user to edit the resource
        //
        $this->assertFalse($service->isAllowedToEditMedia($user1));
    }

    public function testIsAllowedToPreview(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl
            = $this->getActionAccessControlMock(MediaManager::class, 'isPreviewEnabled', false, self::user1Id);

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker grants read access for a known user
        //
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants isPreviewEnabled access
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user1Id);

        // THEN MediaPermissionsService does allow that user to edit the resource
        //
        $this->assertTrue($service->isAllowedToPreview($user1));


        // AND GIVEN a user for which ACL does NOT grant isPreviewEnabled access
        //
        $user2 = $this->createMock(User::class);
        $user2->method('getIdentifier')->willReturn(self::user2Id);

        // THEN MediaPermissionsService does NOT allow that user to preview the resource
        //
        $this->assertFalse($service->isAllowedToPreview($user2));
    }

    public function testIsNotAllowedToPreviewWithDifferentGrant(): void
    {
        // SETUP
        $permissionChecker = $this->getPermissionCheckerMock(self::user1Id);
        $actionAccessControl
            = $this->getActionAccessControlMock(MediaManager::class, 'otherPerm', false, self::user1Id);

        // UNDER TEST
        $service = new MediaPermissionsService($actionAccessControl, $permissionChecker);

        // GIVEN a resource for which PermissionChecker grants write access for a known user
        //
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')->willReturn(self::resource1Id);

        // AND a user for which ACL grants access for an action other than editMedia
        //
        $user1 = $this->createMock(User::class);
        $user1->method('getIdentifier')->willReturn(self::user1Id);

        // THEN MediaPermissionsService does NOT allow that user to preview the resource
        //
        $this->assertFalse($service->isAllowedToPreview($user1));
    }
}
