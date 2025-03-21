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
 * Copyright (c) 2017-2025 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\scripts;

use oat\oatbox\action\Action;
use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Class;
use Exception;
use helpers_TimeOutHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class importMedia
 *
 * Usage:
 *  sudo -u www-data php index.php 'oat\taoMediaManager\scripts\ImportMedia' [PACKAGE] [OPTIONS]
 *
 * Options:
 *   -c <class>   The name of the class in which to import the media
 *   -p <package> The path of a media package (file or folder)
 *   -r           Recurse in subdirectories
 *   -n           Create classes from directories names
 *   -e           Rollback on error
 *   -w           Rollback on warning
 *   -h           Show this help
 *
 */
class importMedia implements Action, ServiceLocatorAwareInterface
{
    use OntologyAwareTrait;
    use ServiceLocatorAwareTrait;

    protected $rollbackOnError = false;
    protected $rollbackOnWarning = false;
    protected $recurse = false;
    protected $directoryToClass = false;
    protected $processed = 0;

    /**
     * Entry point for CLI.
     *
     * @param array $params
     * @return Report
     * @throws \common_Exception
     */
    public function __invoke($params = [])
    {
        $fileName = null;
        $className = null;
        $showHelp = !count($params);

        while (count($params) && !$showHelp) {
            $param = array_shift($params);
            switch ($param) {
                case '-p':
                    $fileName = array_shift($params);
                    break;
                case '-c':
                    $className = array_shift($params);
                    break;
                case '-n':
                    $this->directoryToClass = true;
                    break;
                case '-h':
                    $showHelp = true;
                    break;
                case '-e':
                    $this->rollbackOnError = true;
                    break;
                case '-r':
                    $this->recurse = true;
                    break;
                case '-w':
                    $this->rollbackOnWarning = true;
                    break;
                default:
                    if (file_exists($param)) {
                        $fileName = $param;
                    }
            }
        }

        if ($showHelp) {
            return Report::createSuccess(
                "Import media\n"
                . "\nUsage:\n\tphp index.php '" . __CLASS__ . "' [PACKAGE] [OPTIONS]\n\n"
                . "Options:\n"
                . "\t -c <class>\t The name of the parent class for importing media\n"
                . "\t -p <package>\t The path of a media package (file or folder)\n"
                . "\t -r\t\t Recurse in subdirectories (for folders)\n"
                . "\t -n\t\t Create a new class for each folder \n"
                . "\t -e\t\t Rollback on error\n"
                . "\t -w\t\t Rollback on warning\n"
                . "\t -h\t\t Show this help\n"
            );
        }

        if (!$fileName || !file_exists($fileName)) {
            throw new \common_Exception(
                "You must provide the path of a media package. " .
                (isset($fileName) ? $fileName . " does not exist." : "Nothing provided!")
            );
        }

        // Retrieve the parent class for media. If none is provided, the root media class is used.
        $parentClass = $this->getMediaClass($className);
        $report = $this->importPath($fileName, $parentClass);
        $report->setMessage($this->processed . ' package(s) processed');

        return $report;
    }

    /**
     * Retrieve (or create) a media class based on the provided class name.
     * If no class name is provided, the root media class is returned.
     *
     * @param string|null $className
     * @param string $parentClassUri
     * @return core_kernel_classes_Class|null
     */
    protected function getMediaClass($className = null, $parentClassUri = TaoMediaOntology::CLASS_URI_MEDIA_ROOT)
    {
        $parentClass = $this->getClass($parentClassUri);

        if ($className) {
            $subClass = null;
            $className = str_replace('_', ' ', $className);
            $subClasses = $parentClass->getSubClasses();
            foreach ($subClasses as $instance) {
                if ($instance->getLabel() == $className) {
                    $subClass = $instance;
                    $this->showMessage("Loaded class: $className");
                    break;
                }
            }
            if (!$subClass) {
                $subClass = $parentClass->createSubClass($className);
                $this->showMessage("Created class: $className");
            }
            return $subClass;
        }

        return $parentClass;
    }

    /**
     * Display a message to the CLI.
     *
     * @param string $message
     * @param array $params
     * @param string $type
     */
    protected function showMessage($message, $params = [], $type = Report::TYPE_SUCCESS)
    {
        if ($params) {
            $message .= "\n";
            foreach ($params as $key => $value) {
                $message .= "\t${key}: ${value}\n";
            }
        }
        $this->showReport(new Report($type, $message));
    }

    /**
     * Render a report to the CLI.
     *
     * @param Report $report
     */
    protected function showReport($report)
    {
        echo \tao_helpers_report_Rendering::renderToCommandline($report);
    }

    /**
     * List packages (files and directories) in a given directory.
     *
     * @param string $path
     * @return array Array of arrays with keys 'path' and 'name'
     */
    protected function listPackages($path)
    {
        $packages = array_map(function ($fileName) use ($path) {
            if ($fileName !== '.' && $fileName !== '..') {
                $fullPath = $path . DIRECTORY_SEPARATOR . $fileName;
                $info = pathinfo($fileName);
                return [
                    'path' => $fullPath,
                    'name' => isset($info['filename']) ? $info['filename'] : $fileName,
                ];
            }
            return null;
        }, scandir($path));

        return array_filter($packages, function ($file) {
            return $file !== null;
        });
    }

    /**
     * Import media from a given path.
     *
     * If the path is a directory:
     *   - If directoryToClass is true, create a new class for the folder.
     *   - Otherwise, use the parent class for all files.
     *
     * If recursion is enabled (-r flag):
     *   - If directoryToClass is true, subfolders get their own class (nested under the current folder).
     *   - Otherwise, files from subfolders are imported into the parent class.
     *
     * @param string $path
     * @param core_kernel_classes_Class $parentClass
     * @return Report
     */
    protected function importPath($path, $parentClass)
    {
        if (is_dir($path)) {
            if ($this->directoryToClass) {
                $currentClass = $this->getMediaClass(basename($path), $parentClass->getUri());
            } else {
                $currentClass = $parentClass;
            }
            $packages = $this->listPackages($path);
            $finalReport = new Report(Report::TYPE_SUCCESS);

            foreach ($packages as $package) {
                if (is_dir($package['path'])) {
                    if ($this->recurse) {
                        // If -n is enabled, pass the current folder's class; otherwise, keep using the parent class.
                        $report = $this->importPath(
                            $package['path'],
                            $this->directoryToClass ? $currentClass : $parentClass
                        );
                    } else {
                        $this->showMessage(
                            "Skipping subfolder " . $package['path'] . " (recursion not enabled)",
                            [],
                            Report::TYPE_INFO
                        );
                        continue;
                    }
                } else {
                    $report = $this->importFile($package['path'], $currentClass);
                }
                if ($report && $report->getType() != Report::TYPE_SUCCESS) {
                    $finalReport->setType($report->getType());
                }
            }
            return $finalReport;
        } else {
            return $this->importFile($path, $parentClass);
        }
    }

    /**
     * Import a single media file.
     *
     * @param string $fileName
     * @param core_kernel_classes_Class $class
     * @return Report
     */
    protected function importFile($fileName, $class)
    {
        $this->showMessage("Importing media from $fileName");
        helpers_TimeOutHelper::setTimeOutLimit(helpers_TimeOutHelper::LONG);

        try {
            $mediaService = MediaService::singleton();
            $result = $mediaService->createMediaInstance(
                $fileName,
                $class->getUri(),
                DEFAULT_LANG,
                basename($fileName)
            );
            if ($result !== false) {
                $report = new Report(Report::TYPE_SUCCESS, "Media imported successfully from $fileName");
            } else {
                $report = new Report(Report::TYPE_ERROR, "Failed to import media from $fileName");
            }
        } catch (Exception $e) {
            $report = new Report(
                Report::TYPE_ERROR,
                "An unexpected error occurred while importing $fileName: " . $e->getMessage()
            );
        }

        helpers_TimeOutHelper::reset();
        $this->showReport($report);
        $this->processed++;

        return $report;
    }
}
