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

use common_report_Report as Report;
use oat\oatbox\filesystem\File;
use tao_helpers_form_Form as Form;
use core_kernel_classes_Class;
use oat\tao\model\import\ImportHandlerHelperTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Service methods to manage the Media
 *
 * @access  public
 * @author  Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class ZipImporter implements ServiceLocatorAwareInterface
{
    use ImportHandlerHelperTrait;

    protected $directoryMap = [];

    /**
     * Starts the import based on the form values
     *
     * @param \core_kernel_classes_Class   $class
     * @param \tao_helpers_form_Form|array $form
     * @return \common_report_Report
     */
    public function import($class, $form)
    {
        try {
            $uploadedFile = $this->fetchUploadedFile($form);
            $resource = new core_kernel_classes_Class($form instanceof Form ? $form->getValue('classUri') : $form['classUri']);

            // unzip the file
            try {
                \helpers_TimeOutHelper::setTimeOutLimit(\helpers_TimeOutHelper::LONG);
                $directory = $this->extractArchive($uploadedFile);
                \helpers_TimeOutHelper::reset();
            } catch (\Exception $e) {
                $report = Report::createFailure(__('Unable to extract the archive'));
                $report->setData(['uriResource' => '']);

                return $report;
            }

            // get list of directory in order to create classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $service = MediaService::singleton();
            $language = $form instanceof Form ? $form->getValue('lang') : $form['lang'];

            $this->directoryMap = [
                rtrim($directory, DIRECTORY_SEPARATOR) => $resource->getUri()
            ];
            $report = Report::createSuccess(__('Media imported successfully'));

            /** @var $file \SplFileInfo */
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    \common_Logger::i('File ' . $file->getPathname());
                    if (isset($this->directoryMap[$file->getPath()])) {
                        $classUri = $this->directoryMap[$file->getPath()];
                    } else {
                        $classUri = $this->createClass($file->getPath());
                    }

                    $mediaResourceUri = $service->createMediaInstance($file->getRealPath(), $classUri, $language, $file->getFilename());
                    $report->add(Report::createSuccess(
                        __('Imported %s', substr($file->getRealPath(), strlen($directory))),
                        ['uriResource' => $mediaResourceUri] // 'uriResource' key is needed by javascript in tao/views/templates/form/import.tpl
                    ));
                }
            }
        } catch (\Exception $e) {
            $report = Report::createFailure($e->getMessage());
            $report->setData(['uriResource' => '']);
        }

        return $report;
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
     * @param string|File $archiveFile
     * @return string temporary directory zipfile was extracted to
     *
     * @throws \common_Exception
     */
    protected function extractArchive($archiveFile)
    {
        if ($archiveFile instanceof File) {
            if (!$archiveFile->exists()) {
                throw new \common_Exception('Unable to open archive ' . '/' . $archiveFile->getPrefix());
            }
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

        if ($archiveFile instanceof File) {
            // get a local copy of zip
            $tmpName = \tao_helpers_File::concat([\tao_helpers_File::createTempDir(), $archiveFile->getBasename()]);
            if (($resource = fopen($tmpName, 'wb')) !== false) {
                stream_copy_to_stream($archiveFile->readStream(), $resource);
                fclose($resource);
            }

            $archiveFile = $tmpName;
        }

        $archiveHandle = $archiveObj->open($archiveFile);
        if (true !== $archiveHandle) {
            throw new \common_Exception('Unable to open archive ' . $archiveFile);
        }
        if (\tao_helpers_File::checkWhetherArchiveIsBomb($archiveObj)) {
            throw new \common_Exception(sprintf('Source "%s" seems to be a ZIP bomb', $archiveFile));
        }
        if (!$archiveObj->extractTo($archiveDir)) {
            $archiveObj->close();
            throw new \common_Exception('Unable to extract to ' . $archiveDir);
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
}