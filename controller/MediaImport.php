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

use oat\taoMediaManager\model\ImportHandlerFactory;
use tao_actions_Import;
use tao_models_classes_import_ImportHandler;

/**
 * This controller provide the actions to import medias
 */
class MediaImport extends tao_actions_Import
{
    /** @var tao_models_classes_import_ImportHandler[] */
    private $importHandlers;

    /**
     * @inheritDoc
     */
    public function index()
    {
        $this->importHandlers = $this->getImportHandlerFactory()->createAvailable();

        parent::index();
    }

    public function editMedia()
    {
        $id = $this->hasRequestParameter('instanceUri')
            ? $this->getRequestParameter('instanceUri')
            : $this->getRequestParameter('id');

        $this->importHandlers = [$this->getImportHandlerFactory()->createByMediaId($id)];

        parent::index();
    }

    protected function getAvailableImportHandlers()
    {
        return $this->importHandlers;
    }

    private function getImportHandlerFactory(): ImportHandlerFactory
    {
        return new ImportHandlerFactory($this->getModel());
    }
}
