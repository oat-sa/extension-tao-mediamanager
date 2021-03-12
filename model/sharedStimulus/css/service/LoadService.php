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

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use oat\generis\model\data\Ontology;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\css\LoadCommand;

class LoadService extends ConfigurableService
{
    public const STYLESHEET_WARNING_HEADER = " /* Do not edit */";

    public function load(LoadCommand $command)
    {
        $passageResource = $this->getOntology()->getResource($command->getUri());
        $link = $passageResource->getUniquePropertyValue($passageResource->getProperty(MediaService::PROPERTY_LINK));
        $link = $this->getFileSourceUnserializer()->unserialize((string)$link);

        $path = dirname((string)$link);
        if ($path == '.') {
            throw new \Exception ('Shared stimulus stored as single file');
        }

        $fs = $this->getFileSystem();
        try {
            $content = $fs->read($path . DIRECTORY_SEPARATOR . $command->getStylesheetUri());

            return $this->cssToArray($content);
        } catch (FileNotFoundException $e) {
            \common_Logger::d(
                'Stylesheet ' . $command->getStylesheetUri() . ' does not exist yet, returning empty array'
            );

            return [];
        }
    }

    private function cssToArray(string $css): array
    {
        $oldCssArr = explode("\n", $css);
        array_shift($oldCssArr);

        $newCssArr = [];
        foreach ($oldCssArr as $line) {
            if (false === strpos($line, '{')) {
                continue;
            }

            preg_match('~(?P<selector>[^{]+)(\{)(?P<rules>[^}]+)\}~', $line, $matches);

            foreach ($matches as $key => &$match) {
                if (is_numeric($key)) {
                    continue;
                }
                $match = trim($match);
                if ($key === 'rules') {
                    $ruleSet = array_filter(array_map('trim', explode(';', $match)));
                    $match = [];
                    foreach ($ruleSet as $rule) {
                        $rule = array_map('trim', explode(':', $rule));
                        $match[$rule[0]] = $rule[1];
                    }
                }
            }

            $newCssArr[$matches['selector']] = $matches['rules'];
        }
        return $newCssArr;
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

    private function getFileSourceUnserializer(): FileSourceUnserializer
    {
        return $this->getServiceLocator()->get(FileSourceUnserializer::class);
    }
}
