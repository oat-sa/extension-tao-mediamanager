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

namespace oat\taoMediaManager\model\export\service;

use core_kernel_classes_Resource;
use oat\oatbox\filesystem\FilesystemInterface;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use ZipArchive;

class SharedStimulusCSSExporter extends ConfigurableService
{
    public const CSS_ZIP_DIR_NAME = 'css';

    public function pack(core_kernel_classes_Resource $mediaResource, string $link, ZipArchive $zip): void
    {
        if (!$this->getSharedStimulusResourceSpecification()->isSatisfiedBy($mediaResource)) {
            return;
        }

        $fs = $this->getFileSystem();
        $cssPath = dirname($link) . DIRECTORY_SEPARATOR . StoreService::CSS_DIR_NAME;

        if (!$fs->directoryExists($cssPath)) {
            return;
        }

        $files = $fs->listContents($cssPath);
        if (!count($files->toArray())) {
            return;
        }

        $zip->addEmptyDir(self::CSS_ZIP_DIR_NAME);

        foreach ($files as $file) {
            $content = $fs->read($cssPath . DIRECTORY_SEPARATOR . $file['basename']);
            $zip->addFromString(self::CSS_ZIP_DIR_NAME . DIRECTORY_SEPARATOR . $file['basename'], $content);
        }
    }

    private function getFileSystem(): FilesystemInterface
    {
        return $this->getFileSystemService()
            ->getFileSystem($this->getFlySystemManagement()->getOption(FlySystemManagement::OPTION_FS));
    }

    private function getFileSystemService(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    private function getFlySystemManagement(): FlySystemManagement
    {
        return $this->getServiceLocator()->get(FlySystemManagement::SERVICE_ID);
    }

    private function getSharedStimulusResourceSpecification(): SharedStimulusResourceSpecification
    {
        return $this->getServiceLocator()->get(SharedStimulusResourceSpecification::class);
    }
}
