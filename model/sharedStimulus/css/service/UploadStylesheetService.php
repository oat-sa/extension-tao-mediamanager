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
    public function save(UploadedStylesheet $uploadedStylesheetDTO): array
    {
        $passagePath = $this->getStylesheetRepository()->getPath($uploadedStylesheetDTO->getUri());
        $link = $passagePath
            . DIRECTORY_SEPARATOR
            . StylesheetRepository::STYLESHEETS_DIRECTORY
            . DIRECTORY_SEPARATOR
            . $uploadedStylesheetDTO->getFileName();

        $tmpResource = $uploadedStylesheetDTO->getFileResource();
        $size = filesize($uploadedStylesheetDTO->getTmpFileLink());
        $this->getStylesheetRepository()->writeStream($link, $tmpResource);

        if (is_resource($tmpResource)) {
            fclose($tmpResource);
        } else {
            $this->logWarning(
                sprintf(
                    'Stream for asset stylesheet file "%s" is not valid. It may be already closed',
                    $uploadedStylesheetDTO->getFileName()
                )
            );
        }

        if (is_writable($uploadedStylesheetDTO->getTmpFileLink())) {
            unlink($uploadedStylesheetDTO->getTmpFileLink());
        }

        return [
            'alt' => $uploadedStylesheetDTO->getFileName(),
            'link' => DIRECTORY_SEPARATOR . $uploadedStylesheetDTO->getFileName(),
            'mime' => 'text/css',
            'name' => $uploadedStylesheetDTO->getFileName(),
            'size' => $size,
            'uri' => DIRECTORY_SEPARATOR . $uploadedStylesheetDTO->getFileName()
        ];
    }

    private function getStylesheetRepository(): StylesheetRepository
    {
        return $this->getServiceLocator()->get(StylesheetRepository::class);
    }
}
