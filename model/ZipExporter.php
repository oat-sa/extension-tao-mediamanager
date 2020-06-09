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

namespace oat\taoMediaManager\model;

use common_Exception;
use common_report_Report as Report;
use core_kernel_classes_Class;
use core_kernel_classes_Container;
use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\oatbox\service\ServiceManager;
use oat\taoMediaManager\model\export\service\MediaResourcePreparer;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use tao_helpers_Export;
use tao_models_classes_export_ExportHandler;
use ZipArchive;

/**
 * Service methods to manage the Media
 *
 * @access  public
 * @author  Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class ZipExporter implements tao_models_classes_export_ExportHandler
{
    /**
     * @inheritDoc
     */
    public function getLabel()
    {
        return __('Zip');
    }

    /**
     * @inheritDoc
     */
    public function getExportForm(core_kernel_classes_Resource $resource)
    {
        return (new ZipExportForm(['resource' => $resource]))
            ->getForm();
    }

    /**
     * @inheritDoc
     */
    public function export($formValues, $destPath)
    {
        if (!isset($formValues['filename'])) {
            return Report::createFailure('Missing filename for export using ' . __CLASS__);
        }

        if (!isset($formValues['id'])) {
            return Report::createFailure('No id for export using ' . __CLASS__);
        }

        $report = Report::createSuccess();

        $class = new core_kernel_classes_Class($formValues['id']);

        $exportClasses = [];
        if ($class->isClass()) {
            $subClasses = $class->getSubClasses(true);
            $exportData = [$class->getLabel() => $class->getInstances()];

            foreach ($subClasses as $subClass) {
                $instances = $subClass->getInstances();
                $exportData[$subClass->getLabel()] = $instances;

                //get Class path
                $parents = $subClass->getParentClasses();
                $parent = array_shift($parents);

                if (array_key_exists($parent->getLabel(), $exportClasses)) {
                    $exportClasses[$subClass->getLabel()] = $exportClasses[$parent->getLabel()] . '/' . $subClass->getLabel();
                } else {
                    $exportClasses[$subClass->getLabel()] = $subClass->getLabel();
                }
            }
        } else {
            $exportData = [$class->getLabel() => [$class]];
        }

        $safePath = $this->getSavePath($formValues['filename']);

        $file = $this->createZipFile($safePath, $exportClasses, $exportData);

        $report->setData($file);
        $report->setMessage(__('Media successfully exported.'));

        return $report;
    }

    private function getSavePath(string $unsafePath): string
    {
        $pathInfo = pathinfo($unsafePath);
        $safePath = $pathInfo['filename'];

        if (array_key_exists('extension', $pathInfo)) {
            $safePath .= '.' . $pathInfo['extension'];
        }

        return $safePath;
    }

    protected function createZipFile($filename, array $exportClasses = [], array $exportFiles = []): string
    {
        $zip = new ZipArchive();
        $baseDir = tao_helpers_Export::getExportPath();
        $path = $baseDir . '/' . $filename . '.zip';

        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new common_Exception('Unable to create zipfile ' . $path);
        }

        if ($zip->numFiles === 0) {
            $nbFiles = 0;

            foreach ($exportFiles as $label => $files) {
                $archivePath = '';

                /** @var $class core_kernel_classes_Class */
                if (array_key_exists($label, $exportClasses)) {
                    $archivePath = $exportClasses[$label] . '/';

                    $zip->addEmptyDir($archivePath);

                    $nbFiles++;
                }

                $nbFiles += count($files);
                //create the directory

                /** @var core_kernel_classes_Resource $fileResource */
                foreach ($files as $fileResource) {
                    $link = $this->getResourceLink($fileResource);

                    $fileContent = $this->getFileManagement()
                        ->getFileStream($link)
                        ->getContents();

                    $preparedFileContent = $this->getMediaResourcePreparer()->prepare($fileResource, $fileContent);

                    $zip->addFromString($archivePath . $fileResource->getLabel(), $preparedFileContent);
                }
            }
        }

        $zip->close();

        return $path;
    }

    public function getServiceManager()
    {
        return ServiceManager::getServiceManager();
    }

    private function getFileManagement(): FileManagement
    {
        return $this->getServiceManager()->get(FileManagement::SERVICE_ID);
    }

    private function getMediaResourcePreparer(): MediaResourcePreparer
    {
        return $this->getServiceManager()->get(MediaResourcePreparer::class);
    }

    /**
     * @return core_kernel_classes_Container|string
     *
     * @throws core_kernel_classes_EmptyProperty
     * @throws common_Exception
     */
    private function getResourceLink(core_kernel_classes_Resource $resource)
    {
        $link = $resource->getUniquePropertyValue(new core_kernel_classes_Property(MediaService::PROPERTY_LINK));

        return $link instanceof core_kernel_classes_Literal ? $link->literal : $link;
    }
}
