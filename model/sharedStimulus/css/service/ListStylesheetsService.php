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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2021-2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\css\service;

use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;

class ListStylesheetsService extends ConfigurableService
{
    public function getList(ListStylesheets $listStylesheetsDTO): array
    {
        $stylesheetRepository = $this->getStylesheetRepository();

        $path = $stylesheetRepository->getPath($listStylesheetsDTO->getUri());

        $cssPath = $path . '/' . StylesheetRepository::STYLESHEETS_DIRECTORY;

        $this->logInfo(sprintf('[ListStylesheetsService] Base path: %s', $path));
        $this->logInfo(sprintf('[ListStylesheetsService] Listing CSS files from path: %s', $cssPath));

        $fs = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID)
            ->getFileSystem($this->getServiceLocator()->get(FlySystemManagement::SERVICE_ID)
            ->getOption(FlySystemManagement::OPTION_FS));

        $cssDirectoryExists = $fs->directoryExists($cssPath);

        if (!$cssDirectoryExists) {
            $list = [];
        } else {
            try {
                $list = $stylesheetRepository->listContents($cssPath)->toArray();
            } catch (\Exception $e) {
                $this->logError(sprintf(
                    '[ListStylesheetsService] Failed to list contents of %s: %s',
                    $cssPath,
                    $e->getMessage()
                ));
                $list = [];
            }
        }

        /**
         * here sorting files by creation date so that in case of css .selector collisions
         * the rules will be applied from the last stylesheet added to the passage
         */
        usort($list, function ($a, $b) {
            $a_last_modified = $a['last_modified'] ?? $a['lastModified'] ?? $a['timestamp'] ?? 0;
            $b_last_modified = $b['last_modified'] ?? $b['lastModified'] ?? $b['timestamp'] ?? 0;
            return $a_last_modified <=> $b_last_modified;
        });

        $data = [];
        foreach ($list as $file) {
            if ($file['type'] == 'file') {
                $data[] = [
                    'name' => basename($file['path']),
                    'uri' => '/' . basename($file['path']),
                    'mime' => 'text/css',
                    'filePath' => '/' . basename($file['path']),
                    'size' => $file['file_size'] ?? $file['fileSize'] ?? 0
                ];
            }
        }

        return [
            'path' => '/',
            'label' => 'Passage stylesheets',
            'childrenLimit' => 100,
            'total' => count($data),
            'children' => $data
        ];
    }

    private function getStylesheetRepository(): StylesheetRepository
    {
        return $this->getServiceLocator()->get(StylesheetRepository::class);
    }
}
