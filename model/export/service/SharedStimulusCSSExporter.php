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

use ZipArchive;
use common_Exception;
use core_kernel_classes_Literal;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use core_kernel_classes_Container;
use core_kernel_classes_EmptyProperty;
use League\Flysystem\FilesystemInterface;
use oat\taoMediaManager\model\MediaService;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;

class SharedStimulusCSSExporter extends ConfigurableService
{
    public const CSS_ZIP_DIR_NAME = 'css';

    /** @var core_kernel_classes_Container[]|string[] */
    private $links = [];

    public function pack(
        core_kernel_classes_Resource $mediaResource,
        ZipArchive $zip,
        ?string $parentCssZipDirectoryPath = null
    ): void {
        if (!$this->getSharedStimulusResourceSpecification()->isSatisfiedBy($mediaResource)) {
            return;
        }

        $fs = $this->getFileSystem();
        $cssPath = dirname($this->getResourceLink($mediaResource)) . DIRECTORY_SEPARATOR . StoreService::CSS_DIR_NAME;

        if (!$fs->has($cssPath)) {
            return;
        }

        $files = $fs->listContents($cssPath);
        if (empty($files)) {
            return;
        }

        $cssZipDirectoryPath = $this->getCssZipDirectoryPath($parentCssZipDirectoryPath);
        $zip->addEmptyDir($cssZipDirectoryPath);

        foreach ($files as $file) {
            $content = $fs->read($cssPath . DIRECTORY_SEPARATOR . $file['basename']);
            $zip->addFromString($cssZipDirectoryPath . DIRECTORY_SEPARATOR . $file['basename'], $content);
        }
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     *
     * @return core_kernel_classes_Container|string
     */
    public function getResourceLink(core_kernel_classes_Resource $resource)
    {
        $resourceUri = $resource->getUri();

        if (!array_key_exists($resourceUri, $this->links)) {
            $link = $resource->getUniquePropertyValue(
                new core_kernel_classes_Property(MediaService::PROPERTY_LINK)
            );

            $this->links[$resourceUri] = $link instanceof core_kernel_classes_Literal
                ? $link->literal
                : $link;
        }

        return $this->links[$resourceUri];
    }

    private function getCssZipDirectoryPath(?string $parentCssZipDirectoryPath = null): string
    {
        return $parentCssZipDirectoryPath !== null
            ? $parentCssZipDirectoryPath . DIRECTORY_SEPARATOR . self::CSS_ZIP_DIR_NAME
            : self::CSS_ZIP_DIR_NAME;
    }

    private function getFileSystem(): FilesystemInterface
    {
        return $this
            ->getFileSystemService()
            ->getFileSystem(
                $this->getFlySystemManagement()->getOption(FlySystemManagement::OPTION_FS)
            );
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
