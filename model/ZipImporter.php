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

use core_kernel_classes_Class;
use tao_helpers_form_Form;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager

 */
class ZipImporter
{

    /**
     * @var string $directory
     */
    private $directory;

    public function __construct($directory = ''){
        $this->directory = $directory;
    }

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

        try{
            $file = $form->getValue('source');
            $resource = new core_kernel_classes_Class($form->getValue('classUri'));

            $tmpDir = \tao_helpers_File::createTempDir();
            $fileName = \tao_helpers_File::getSafeFileName($file['name']);
            $filePath = $tmpDir . '/' . $fileName;
            if (!rename($file['uploaded_file'], $filePath)) {
                return \common_report_Report::createFailure(__('Unable to move uploaded file'));
            }

            // unzip the file
            if (!$this->extractArchive($filePath)) {
                $report = \common_report_Report::createFailure($this->getDirectory());
                return $report;
            }

            // get list of directory in order to create classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getDirectory()),
                \RecursiveIteratorIterator::LEAVES_ONLY);

            $parents = array();

            $service = MediaService::singleton();
            $language = $form->getValue('lang');

            $rootNode = basename($fileName,'.zip');
            /** @var $file \SplFileInfo */
            foreach($iterator as $file) {
                    if(strpos($file->getPath(), $rootNode) !== false){
                        // get path from root
                        $path = substr($file->getPath(), (strpos($file->getPath(), $rootNode) + strlen($rootNode)));
                        $path = explode('/',$path);

                        //get Parent path to have the architecture
                        $parentPath = explode('/',$file->getPath());
                        unset($parentPath[count($parentPath) - 1]);
                        $parentPath = implode('/',$parentPath);
                        //create class structure
                        if($file->isDir()) {
                            if(count($path) > 2){
                                //do not create multiple time the same class
                                if(!isset($parents[$file->getPath()]) || !in_array($file->getPath(), array_keys($parents))){
                                    // create classes children of root
                                    if($path[count($path) - 2] === ''){
                                        $childClazz = $service->createSubClass($resource, $path[count($path) - 1]);
                                        if(!isset($parents[$file->getPath()])){
                                            $parents[$file->getPath()] = $childClazz;
                                        }
                                    }

                                    //if parent exists just create a subclass
                                    if(isset($parents[$parentPath])){
                                        $clazz = $parents[$parentPath];
                                        $childClazz = $service->createSubClass($clazz, $path[count($path) - 1]);
                                        if(!isset($parents[$file->getPath()])){
                                            $parents[$file->getPath()] = $childClazz;
                                        }
                                    }
                                    //if it doesn't exist create the class and create the subclass
                                    else{
                                        $clazz = $service->createSubClass($resource, $path[count($path) - 2]);
                                        $parents[$parentPath] = $clazz;

                                        $childClazz = $service->createSubClass($clazz, $path[count($path) - 1]);
                                        if(!isset($parents[$file->getPath()])){
                                            $parents[$file->getPath()] = $childClazz;
                                        }
                                    }
                                }
                            }

                        }
                        // get list of files and parent class to create instances
                        else if($file->isFile()){
                            if($path[count($path) - 1] === ""){
                                // create media instance under root class
                                $service->createMediaInstance($file->getRealPath(), $resource->getUri(), $language, $file->getFilename());
                            }
                            else{
                                // create media instance
                                if(isset($parents[$file->getPath()])){
                                    $clazz = $parents[$file->getPath()];
                                    $service->createMediaInstance($file->getRealPath(), $clazz->getUri(), $language, $file->getFilename());
                                }
                            }
                        }
                    }
            }

            $report = \common_report_Report::createSuccess(__('Media imported successfully'));
            return $report;

        } catch(\Exception $e){
            $report = \common_report_Report::createFailure($e->getMessage());
            return $report;
        }
    }


    /**
     * Unzip archive from the upload form
     *
     * @param $archiveFile
     * @return bool whether it fail or succeed
     */
    protected function extractArchive($archiveFile)
    {
        $archiveDir    = \tao_helpers_File::createTempDir();
        $archiveObj    = new \ZipArchive();
        $archiveHandle = $archiveObj->open($archiveFile);
        if (true !== $archiveHandle) {
            return array('error' => 'Could not open archive');
        }

        if (!$archiveObj->extractTo($archiveDir)) {
            $archiveObj->close();
            $this->directory = array('error' => 'Could not extract archive');
            return false;
        }
        $archiveObj->close();
        $this->directory = $archiveDir.basename($archiveFile,'.zip');
        return true;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }
}
