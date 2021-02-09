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

namespace oat\taoMediaManager\model\sharedStimulus\service;

use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FileManagement;


class StoreService extends ConfigurableService
{

    /**
     * name of sub-directory to store stylesheets
     */
    public const CSS_DIR_NAME = 'CSS';

    /**
     * Stores Shared Stimulus and CSS to own directory and returns it's path
     *
     * @param $stimulusXmlSource
     * @param $stimulusFilename
     * @param $cssFiles
     * @return string
     */
    public function store($stimulusXmlSource, $stimulusFilename, $cssFiles): string
    {
        $fileManager = $this->getFileManagement();

        $dirname = uniqid(hash('crc32', $stimulusFilename));

        $fileManager->createDir($dirname);
        $fileManager->writeStream($dirname . DIRECTORY_SEPARATOR . $stimulusFilename, fopen($stimulusXmlSource, 'r'));

        if (count($cssFiles)) {
            $fileManager->createDir($dirname . DIRECTORY_SEPARATOR. self::CSS_DIR_NAME);
            foreach ($cssFiles as $file) {
                $fileManager->writeStream($dirname . DIRECTORY_SEPARATOR . self::CSS_DIR_NAME . DIRECTORY_SEPARATOR . basename($file), fopen($file, 'r'));
            }
        }

        return $dirname;
    }

    private function getFileManagement(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }
}
