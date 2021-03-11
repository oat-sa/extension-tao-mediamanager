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

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\css\service;

use League\Flysystem\FilesystemInterface;
use oat\generis\model\data\Ontology;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\css\SaveCommand;

class SaveService extends ConfigurableService
{
    public const STYLESHEET_WARNING_HEADER = " /* Do not edit */";

    public function save(SaveCommand $command): void
    {
        $passageResource = $this->getOntology()->getResource($command->getUri());
        $link = $passageResource->getUniquePropertyValue($passageResource->getProperty(MediaService::PROPERTY_LINK));

        $path = dirname((string)$link);
        if ($path == '.') {
            throw new \Exception ('Shared stimulus stored as single file');
        }

        $content = $this->getCssContentFromArray($command->getCssClassesArray());

        $fs = $this->getFileSystem();
        $fs->put($path . DIRECTORY_SEPARATOR . $command->getStylesheetUri(), $content);
    }

    private function getCssContentFromArray(array $array): string
    {
        // Todo clarify if can we use taoQtiItem/helpers/CssHelper.php here? For now duplicating the code
        $css = self::STYLESHEET_WARNING_HEADER . "\n";

        // rebuild CSS
        foreach ($array as $key1 => $value1) {
            $css .= $key1 . '{';

            foreach ($value1 as $key2 => $value2) {
                // in the case that the code is embedded in a media query
                if (is_array($value2)) {
                    foreach ($value2 as $value3) {
                        $css .= $key2 . '{';
                        foreach ($value3 as $mProp) {
                            $css .= $mProp . ':' . $value3 . ';';
                        }
                        $css .= '}';
                    }
                } // regular selectors
                else {
                    $css .= $key2 . ':' . $value2 . ';';
                }
            }
            $css .= "}\n";
        }
        return $css;
    }

    private function getOntology(): Ontology
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID);
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
}
