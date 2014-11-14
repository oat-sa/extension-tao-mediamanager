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
use oat\tao\helpers\FileUploadException;
use tao_helpers_form_Form;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager

 */
class ZipImporter implements \tao_models_classes_import_ImportHandler
{


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
     * Returns a form in order to prepare the import
     * if the import is from a file, the form should include the file element
     *
     * @param array $data the users tree selection
     * @return tao_helpers_form_Form
     */
    public function getForm()
    {
        $form = new ZipImportForm();
        return $form->getForm();
    }

    /**
     * Starts the import based on the form
     *
     * @param \core_kernel_classes_Class $pClass
     * @param \tao_helpers_form_Form $pForm
     */
    public function import($class, $form)
    {
        //as upload may be called multiple times, we remove the session lock as soon as possible
        session_write_close();

        try{
            $file = $form->getValue('source');


            $tmpDir = \tao_helpers_File::createTempDir();
            $fileName = \tao_helpers_File::getSafeFileName($file['name']);
            $filePath = $tmpDir . '/' . $fileName;
            if (!rename($file['uploaded_file'], $filePath)) {
                return array('error' => __('Unable to move uploaded file'));
            }

            // unzip the file
            $extractResult = $this->extractArchive($filePath);
            if (!empty($extractResult['error'])) {
                $report = \common_report_Report::createFailure($extractResult['error']);
                return $report;
            }

            // get list of directory in order to create classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractResult.'/'.basename($fileName, '.zip')),
                \RecursiveIteratorIterator::LEAVES_ONLY);

            $dirs = array();
            $files = array();
            /** @var $file \SplFileInfo */
            foreach($iterator as $file) {
                if($file->isDir()) {
                    // get path from root
                    $path = substr($file->getPath(), (strpos($file->getPath(), basename($fileName, '.zip')) + strlen(basename($fileName, '.zip'))));
                    $path = explode('/',$path);

                    if(count($path) === 2){
                        if(!isset($dirs[basename($fileName, '.zip')]) || !in_array($path[1],$dirs[basename($fileName, '.zip')])){
                            $dirs[basename($fileName, '.zip')][] = $path[1];
                        }
                    }
                    else if(count($path) > 2){
                        if(!isset($dirs[$path[count($path) - 2]]) || !in_array($path[count($path) - 1],$dirs[$path[count($path) - 2]])){
                            $dirs[$path[count($path) - 2]][] = $path[count($path) - 1];
                        }
                    }
                }
                // get list of files and parent class to create instances
                else if($file->isFile()){
                    $path = substr($file->getPath(), (strpos($file->getPath(), basename($fileName, '.zip')) + strlen(basename($fileName, '.zip'))));
                    $path = explode('/',$path);

                    if($path[count($path) - 1] === ""){
                        $files[basename($fileName, '.zip')] = $file;
                    }
                    else{
                        $files[$path[count($path) - 1]][] = $file;
                    }



                }
            }

            // create classes
            $service = MediaService::singleton();
            $parents = $service->createTreeFromZip($dirs, basename($fileName, '.zip'),$form->getValue('classUri'));

            $language = $form->getValue('lang');
            // iterate through files and create instances
            foreach($files as $parent => $arrayFile){
                $classUri = $parents[$parent]->getUri();
                foreach($arrayFile as $file){
                    $service->createMediaInstance($file->getPath()."/".$file->getFilename(), $classUri, $language);
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
     * Unzip archive from icomoon
     *
     * @param $archiveFile
     * @return array|string
     */
    protected function extractArchive($archiveFile)
    {
        $archiveDir    = dirname($archiveFile);
        $archiveObj    = new \ZipArchive();
        $archiveHandle = $archiveObj->open($archiveFile);
        if (true !== $archiveHandle) {
            return array('error' => 'Could not open archive');
        }

        if (!$archiveObj->extractTo($archiveDir)) {
            $archiveObj->close();
            return array('error' => 'Could not extract archive');
        }
        $archiveObj->close();
        return $archiveDir;
    }
}
