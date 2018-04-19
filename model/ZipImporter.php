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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoMediaManager\model;

use core_kernel_classes_Class;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\upload\UploadService;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class ZipImporter
{

    protected $directoryMap = array();

    /**
     * Starts the import based on the form
     *
     * @param \core_kernel_classes_Class $class
     * @param \tao_helpers_form_Form $form
     * @return \common_report_Report
     */
    public function import($class, $form)
    {
        //as upload may be called multiple times, we remove the session lock as soon as possible
        session_write_close();

        try {
            $file = $form->getValue('source');
            $resource = new core_kernel_classes_Class($form->getValue('classUri'));

            /** @var  UploadService $uploadService */
            $uploadService = $this->getServiceLocator()->get(UploadService::SERVICE_ID);
            $uploadedFile = $uploadService->getUploadedFlyFile($file['uploaded_file']);

            // unzip the file
            try {
                \helpers_TimeOutHelper::setTimeOutLimit(\helpers_TimeOutHelper::LONG);
                $directory = $this->extractArchive($uploadedFile);
                \helpers_TimeOutHelper::reset();
            } catch (\Exception $e) {
                return \common_report_Report::createFailure(__('Unable to extract the archive'));
            }

            // get list of directory in order to create classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY);
            
            $service = MediaService::singleton();
            $language = $form->getValue('lang');

            $this->directoryMap = array(
            	rtrim($directory, DIRECTORY_SEPARATOR) => $resource->getUri()
            );
            /** @var $file \SplFileInfo */
            $report = \common_report_Report::createSuccess(__('Media imported successfully'));
            foreach ($iterator as $file) {
                
                if ($file->isFile()) {
                    \common_Logger::i('File '.$file->getPathname());
                    if (isset($this->directoryMap[$file->getPath()])) {
                        $classUri = $this->directoryMap[$file->getPath()];
                    } else {
                        $classUri = $this->createClass($file->getPath());
                    }
                    
                    $service->createMediaInstance($file->getRealPath(), $classUri, $language, $file->getFilename());
                    $report->add(\common_report_Report::createSuccess(__('Imported %s', substr($file->getRealPath(), strlen($directory)))));
                }
            }

            return $report;

        } catch (\Exception $e) {
            $report = \common_report_Report::createFailure($e->getMessage());
            return $report;
        }
    }

    /**
     * @param $relPath
     * @return string
     * @throws \common_exception_Error
     */
    protected function createClass($relPath)
    {
        $parentPath = dirname($relPath);
        if (isset($this->directoryMap[$parentPath])) {
            $parentUri = $this->directoryMap[$parentPath];
        } else {
            $parentUri = $this->createClass($parentPath);
        }
        $parentClass = new \core_kernel_classes_Class($parentUri);
        $childClazz = MediaService::singleton()->createSubClass($parentClass, basename($relPath));
        $this->directoryMap[$relPath] = $childClazz->getUri();
        return $childClazz->getUri();
    }
    
    /**
     * Unzip archive from the upload form
     *
     * @param $archiveFile
     * @return string temporary directory zipfile was extracted to
     *
     * @throws \common_Exception
     */
    protected function extractArchive($archiveFile)
    {
        if ($archiveFile instanceof File) {
            $tmpDir = \tao_helpers_File::createTempDir();
            $tmpFilePath = $tmpDir . uniqid('sharedStimulus-import') . '.zip';
            $tmpFile = fopen($tmpFilePath, 'w');
            $originalPackage = $archiveFile->readStream();
            stream_copy_to_stream($originalPackage, $tmpFile);
            fclose($originalPackage);
            fclose($tmpFile);

            $archiveFile = $tmpFilePath;
        }

        $archiveDir = \tao_helpers_File::createTempDir();
        $archiveObj = new \ZipArchive();
        $archiveHandle = $archiveObj->open($archiveFile);
        if (true !== $archiveHandle) {
            throw new \common_Exception('Unable to open archive '.$archiveFile);
        }

        if (!$archiveObj->extractTo($archiveDir)) {
            $archiveObj->close();
            throw new \common_Exception('Unable to extract to '.$archiveDir);
        }
        $archiveObj->close();

        if (isset($tmpFilePath) && file_exists($tmpFilePath)) {
            unlink($tmpFilePath);
        }
        if (isset($tmpDir) && file_exists($tmpDir)) {
            rmdir($tmpDir);
        }

        return $archiveDir;
    }

    /**
     * Get the service Locator
     *
     * @return ServiceManager
     */
    protected function getServiceLocator()
    {
        return ServiceManager::getServiceManager();
    }

}
