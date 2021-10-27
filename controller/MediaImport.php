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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoMediaManager\controller;

use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\taoMediaManager\model\ImportHandlerFactory;
use oat\taoMediaManager\model\MediaPermissionsService;
use tao_actions_Import;
use tao_models_classes_import_ImportHandler;

/**
 * This controller provides the actions to import medias
 */
class MediaImport extends tao_actions_Import
{
    /** @var tao_models_classes_import_ImportHandler[] */
    private $importHandlers;

    /**
     * @inheritDoc
     *
     * @requiresRight id WRITE
     * @requiresRight classUri WRITE
     */
    public function index()
    {
        $this->importHandlers = $this->getImportHandlerFactory()->createAvailable();

        parent::index();
    }

    /**
     * This action is called when requesting or submitting the upload form.
     */
    public function editMedia()
    {
        $id = $this->hasRequestParameter('instanceUri')
            ? $this->getRequestParameter('instanceUri')
            : $this->getRequestParameter('id');

        if (empty($id)) { // Fail fast if the ID is empty
            $this->returnError('Access denied', true, 403);
            return;
        }

        $permissionService = $this->getPermissionsService();
        $resource = $this->getResource($id);
        $user = $this->getSession()->getUser();

        if (!$permissionService->isAllowedToImportMedia($user, $resource)) {
            $this->returnError('Access denied', true, 403);
        } else {
            $this->importHandlers = [$this->getImportHandlerFactory()->createByMediaId($id)];

            parent::index();
        }
    }

    protected function getAvailableImportHandlers()
    {
        return $this->importHandlers;
    }

    private function getImportHandlerFactory(): ImportHandlerFactory
    {
        return $this->getPsrContainer()->get(ImportHandlerFactory::class);
    }

    private function getPermissionsService(): MediaPermissionsService
    {
        $acl = $this->getPsrContainer()->get(ActionAccessControl::class);
        $perm = $this->getPsrContainer()->get(PermissionChecker::class);
        return new MediaPermissionsService($acl, $perm);
    }
}
