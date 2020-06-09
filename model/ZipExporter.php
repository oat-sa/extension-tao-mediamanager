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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\model;

use common_Exception;
use common_report_Report as Report;
use core_kernel_classes_Class;
use core_kernel_classes_Container;
use core_kernel_classes_EmptyProperty;
use core_kernel_classes_Literal;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\log\LoggerService;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\media\TaoMediaException;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoQtiItem\model\qti\Parser;
use qtism\data\content\xhtml\Img;
use qtism\data\storage\xml\XmlDocument;
use tao_helpers_Export;
use tao_helpers_form_Form;
use ZipArchive;

/**
 * Service methods to manage the Media
 *
 * @access  public
 * @author  Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class ZipExporter implements \tao_models_classes_export_ExportHandler
{
    use OntologyAwareTrait;

    /**
     * Returns a textual description of the import format
     *
     * @return string
     */
    public function getLabel()
    {
        return __('Zip');
    }

    /**
     * Returns a form in order to prepare the
     *
     * @param core_kernel_classes_Resource $resource the users selected resource or class
     * @return tao_helpers_form_Form
     */
    public function getExportForm(core_kernel_classes_Resource $resource)
    {
        return (new ZipExportForm(['resource' => $resource]))
            ->getForm();
    }

    /**
     * @param array  $formValues
     * @param string $destPath
     * @return \common_report_Report|null|string
     * @throws common_Exception
     * @throws \common_exception_Error
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

    /**
     * @param $unsafePath
     * @return string safe path
     */
    private function getSavePath($unsafePath)
    {
        $pathInfo = pathinfo($unsafePath);
        $safePath = $pathInfo['filename'];
        if (array_key_exists('extension', $pathInfo)) {
            $safePath .= '.' . $pathInfo['extension'];
        }
        return $safePath;
    }

    protected function createZipFile($filename, array $exportClasses = [], array $exportFiles = [])
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

                /** @var core_kernel_classes_Resource $file */
                foreach ($files as $file) {
                    $link = $this->getResourceLink($file);

                    $fileContents = $this->getFileManagement()
                        ->getFileStream($link)
                        ->getContents();

                    //FIXME
                    //FIXME
                    $this->parseImages($fileContents);
                    //FIXME
                    //FIXME

                    $zip->addFromString($archivePath . $file->getLabel(), $fileContents);
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

    private function parseImages(string $fileContents)
    {
        $mediaResolver = new TaoMediaResolver();

        /** @var LoggerService $logger */
        $logger = $this->getServiceManager()->get(LoggerService::SERVICE_ID);

        $logger->logCritical('================> EXPORTING ' . $fileContents);

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadFromString($fileContents);

        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');

        /** @var Img $image */
        foreach ($images as $image) {
            try {
                $mediaAsset = $mediaResolver->resolve($image->getSrc());

                if ($mediaAsset->getMediaSource() instanceof MediaSource) {
                    $logger->logCritical('================>> IMAGE ' . $image->getSrc());

                    $mediaResource = $this->getResource($mediaAsset->getMediaIdentifier());

                    $link = $this->getResourceLink($mediaResource);

                    $stream = $this->getFileManagement()->getFileStream($link);
                }
            } catch (TaoMediaException $exception) {
            }
        }
    }

    private function secureEncode($basedir, $source): string
    {
        $components = parse_url($source);
        if (!isset($components['scheme'])) {
            // relative path
            if (\tao_helpers_File::securityCheck($source, false)) {
                if (file_exists($basedir . $source)) {
                    return 'data:' . \tao_helpers_File::getMimeType($basedir . $source) . ';'
                        . 'base64,' . base64_encode(file_get_contents($basedir . $source));
                } else {
                    throw new \tao_models_classes_FileNotFoundException($source);
                }
            } else {
                throw new \common_exception_Error('Invalid source path "' . $source . '"');
            }
        } else {
            // url, just return it as is
            return $source;
        }
    }

    private function getFileManagement(): FileManagement
    {
        return $this->getServiceManager()->get(FileManagement::SERVICE_ID);
    }

    /**
     * @return core_kernel_classes_Container|string
     *
     * @throws core_kernel_classes_EmptyProperty
     * @throws common_Exception
     */
    private function getResourceLink(core_kernel_classes_Resource $resource)
    {
        $link = $resource->getUniquePropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK));

        return $link instanceof core_kernel_classes_Literal ? $link->literal : $link;
    }
}
