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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\css\service;

use oat\taoMediaManager\model\sharedStimulus\css\dto\UploadedStylesheet;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;

class UploadStylesheetService extends ConfigurableService
{
    public function save(UploadedStylesheet $uploadedStylesheetDTO): void
    {
        $path = $this->getStylesheetRepository()->getPath($uploadedStylesheetDTO->getUri());
        $stylesheetName = $uploadedStylesheetDTO->getFileName();
        $link = $path
            . DIRECTORY_SEPARATOR
            . StylesheetRepository::STYLESHEETS_DIRECTORY
            . DIRECTORY_SEPARATOR
            . $stylesheetName;

        $this->getStylesheetRepository()->putStream($link, $uploadedStylesheetDTO->getFileResource());
    }

    private function getStylesheetRepository(): StylesheetRepository
    {
        return $this->getServiceLocator()->get(StylesheetRepository::class);
    }
}
