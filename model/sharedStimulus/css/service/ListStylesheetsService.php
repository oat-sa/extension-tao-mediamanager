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

namespace oat\taoMediaManager\model\sharedStimulus\css\service;

use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;

class ListStylesheetsService extends ConfigurableService
{
    public function getList(ListStylesheets $listStylesheetsDTO): array
    {
        $stylesheetRepository = $this->getStylesheetRepository();

        $path = $stylesheetRepository->getPath($listStylesheetsDTO->getUri());
        $list = $stylesheetRepository->listContents(
            $path . DIRECTORY_SEPARATOR . StylesheetRepository::STYLESHEETS_DIRECTORY
        );

        return array_column($list, 'basename');
    }

    private function getStylesheetRepository(): StylesheetRepository
    {
        return $this->getServiceLocator()->get(StylesheetRepository::class);
    }
}
