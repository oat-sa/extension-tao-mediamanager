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

use oat\generis\model\data\Ontology;
use League\Flysystem\FilesystemInterface;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\MediaService;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\css\CommandInterface;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;

abstract class ConfigurableCssService extends ConfigurableService
{
    protected function getPath(CommandInterface $command): string
    {
        $passageResource = $this->getOntology()->getResource($command->getUri());
        $link = $passageResource->getUniquePropertyValue($passageResource->getProperty(MediaService::PROPERTY_LINK));
        $link = $this->getFileSourceUnserializer()->unserialize((string) $link);

        return dirname((string) $link);
    }

    protected function getOntology(): Ontology
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID);
    }

    protected function getFileSourceUnserializer(): FileSourceUnserializer
    {
        return $this->getServiceLocator()->get(FileSourceUnserializer::class);
    }

    protected function getFileSystem(): FilesystemInterface
    {
        $flySystemManagementFs = $this->getFlySystemManagement()->getOption(FlySystemManagement::OPTION_FS);

        return $this->getFileSystemService()->getFileSystem($flySystemManagementFs);
    }

    protected function getFileSystemService(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    protected function getFlySystemManagement(): FlySystemManagement
    {
        return $this->getServiceLocator()->get(FlySystemManagement::SERVICE_ID);
    }

    protected function getFileManagement(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }
}
