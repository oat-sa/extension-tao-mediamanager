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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\scripts;

use oat\oatbox\action\Action;
use common_report_Report as Report;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\TaoMediaOntology;

/**
 * Class ImportMedia
 *
 * Used to import media from the command line
 *
 * ```
 * sudo -u www-data php index.php 'oat\taoMediaManager\scripts\ImportMedia' big_bad_video.mp4
 * 'http://sample/mediaclass.rdf#i1464967192451980'
 * ```
 */
class ImportMedia implements Action
{
    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        if (count($params) < 1) {
            return new Report(Report::TYPE_ERROR, __('Usage: ImportMedia MEDIA_FILE_OR_FOLDER [DESTINATION_CLASS]'));
        }

        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoMediaManager');

        $path = array_shift($params);
        $destinationClassUri = count($params) > 0
            ? array_shift($params)
            : TaoMediaOntology::CLASS_URI_MEDIA_ROOT;

        $service = MediaService::singleton();

        // Check if the given path is a directory
        if (is_dir($path)) {
            $uris = [];
            // Scan the directory for files
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
                // Only process files (ignoring subdirectories)
                if (is_file($fullPath)) {
                    $uri = $service->createMediaInstance(
                        $fullPath,
                        $destinationClassUri,
                        DEFAULT_LANG,
                        basename($fullPath)
                    );
                    if ($uri !== false) {
                        $uris[] = $uri;
                    }
                }
            }

            if (!empty($uris)) {
                $report = new Report(Report::TYPE_SUCCESS, __('Media imported'));
                $report->setData($uris);
            } else {
                $report = new Report(Report::TYPE_ERROR, __('Unable to import any media'));
            }
        } else {
            // Treat $path as a file
            $uri = $service->createMediaInstance($path, $destinationClassUri, DEFAULT_LANG, basename($path));
            if ($uri !== false) {
                $report = new Report(Report::TYPE_SUCCESS, __('Media imported'));
                $report->setData($uri);
            } else {
                $report = new Report(Report::TYPE_ERROR, __('Unable to import'));
            }
        }

        return $report;
    }
}
