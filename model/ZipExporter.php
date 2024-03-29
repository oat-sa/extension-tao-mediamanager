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
 * Copyright (c) 2014-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model;

use common_Exception;
use common_exception_UserReadableException;
use common_report_Report as Report;
use core_kernel_classes_Class;
use core_kernel_classes_Container;
use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use Exception;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\taoMediaManager\model\export\service\MediaResourcePreparerInterface;
use oat\taoMediaManager\model\export\service\SharedStimulusCSSExporter;
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
    use LoggerAwareTrait;

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

        $report->setData($this->processExport($formValues));
        $report->setMessage(__('Media successfully exported.'));

        return $report;
    }

    private function processExport(array $formValues): string
    {
        $class = new core_kernel_classes_Class($formValues['id']);
        $exportClasses = [];

        if ($class->isClass()) {
            $exportData = [
                $class->getLabel() => $this->getClassResources($class)
            ];

            foreach ($class->getSubClasses(true) as $subClass) {
                $instances = $this->getClassResources($subClass);

                if (count($instances) === 0) {
                    continue;
                }

                $exportData[$subClass->getLabel()] = $instances;

                $exportClasses[$subClass->getLabel()] = $this->normalizeClassName($subClass, $exportClasses);
            }
        } else {
            $exportData = [$class->getLabel() => [$class]];
        }

        $safePath = $this->getSavePath($formValues['filename']);

        return $this->createZipFile($safePath, $exportClasses, $exportData);
    }

    private function normalizeClassName(core_kernel_classes_Class $class, array $exportClasses): string
    {
        $parents = $class->getParentClasses();
        $parent = array_shift($parents);

        return array_key_exists($parent->getLabel(), $exportClasses)
            ? $exportClasses[$parent->getLabel()] . '/' . $class->getLabel()
            : $class->getLabel();
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

    /**
     * @throws common_Exception
     * @throws Exception
     */
    protected function createZipFile($filename, array $exportClasses = [], array $exportFiles = []): string
    {
        try {
            $errors = [];

            $baseDir = tao_helpers_Export::getExportPath();
            $path = $baseDir . '/' . $filename . '.zip';

            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE) !== true) {
                throw new common_Exception('Unable to create zipfile ' . $path);
            }

            if ($zip->numFiles !== 0) {
                $zip->close();

                return $path;
            }

            foreach ($exportFiles as $label => $files) {
                $archivePath = '';

                /** @var $class core_kernel_classes_Class */
                if (array_key_exists($label, $exportClasses)) {
                    $archivePath = $exportClasses[$label] . '/';

                    $zip->addEmptyDir($archivePath);
                }

                //create the directory

                /** @var core_kernel_classes_Resource $fileResource */
                foreach ($files as $fileResource) {
                    try {
                        $link = $this->getResourceLink($fileResource);

                        $fileContent = $this->getFileManagement()
                            ->getFileStream($link);

                        $preparedFileContent = $this->getMediaResourcePreparer()->prepare($fileResource, $fileContent);
                        $zip->addFromString($archivePath . $fileResource->getLabel(), $preparedFileContent);

                        $this->getSharedStimulusCSSExporter()->pack($fileResource, $link, $zip);
                    } catch (common_exception_UserReadableException $exception) {
                        $errors[] = sprintf("Error in Asset class \"%s\": %s", $label, $exception->getUserMessage());
                    }
                }
            }

            if (!empty($errors)) {
                throw new ZipExporterFileErrorList($errors);
            }

            $zip->close();

            return $path;
        } catch (Exception $e) {
            $this->getLogger()->error($e->getMessage() . $e->getTraceAsString());

            $zip->close();

            if (is_file($path)) {
                unlink($path);
            }

            throw $e;
        }
    }

    public function getServiceManager()
    {
        return ServiceManager::getServiceManager();
    }

    private function getFileManagement(): FileManagement
    {
        return $this->getServiceManager()->get(FileManagement::SERVICE_ID);
    }

    private function getMediaResourcePreparer(): MediaResourcePreparerInterface
    {
        return $this->getServiceManager()->get(MediaResourcePreparerInterface::SERVICE_ID);
    }

    private function getSharedStimulusCSSExporter(): SharedStimulusCSSExporter
    {
        return $this->getServiceManager()->get(SharedStimulusCSSExporter::class);
    }

    private function getClassResources(core_kernel_classes_Class $class): array
    {
        return $class->getInstances();
    }

    /**
     * @return core_kernel_classes_Container|string
     *
     * @throws core_kernel_classes_EmptyProperty
     * @throws common_Exception
     */
    private function getResourceLink(core_kernel_classes_Resource $resource)
    {
        $link = $resource->getUniquePropertyValue(
            new core_kernel_classes_Property(TaoMediaOntology::PROPERTY_LINK)
        );

        return $link instanceof core_kernel_classes_Literal ? $link->literal : $link;
    }
}
