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
use Jig\Utils\FsUtils;
use qtism\data\storage\xml\XmlDocument;
use tao_helpers_form_Form;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @package taoMediaManager
 */
class SharedStimulusPackageImporter extends ZipImporter
{

    /**
     * Starts the import based on the form
     *
     * @param \core_kernel_classes_Class $class
     * @param \tao_helpers_form_Form $form
     * @return \common_report_Report
     */
    public function import($class, $form)
    {
        \helpers_TimeOutHelper::setTimeOutLimit(\helpers_TimeOutHelper::LONG);
        try {
            $fileInfo = $form->getValue('source');
            $xmlFile = $this->getSharedStimulusFile($fileInfo['uploaded_file']);
            
            // throws an exception of invalid
            SharedStimulusImporter::isValidSharedStimulus($xmlFile);
            
            $embeddedFile = $this->embedAssets($xmlFile);
            $report = $this->storeSharedStimulus(
                $class,
                \tao_helpers_Uri::decode($form->getValue('lang')),
                $embeddedFile
            );
        } catch (\Exception $e) {
            $report = \common_report_Report::createFailure($e->getMessage());
        }
        \helpers_TimeOutHelper::reset();
        return $report;
    }


    /**
     * @param \core_kernel_classes_Resource $instance
     * @param \tao_helpers_form_Form $form
     * @return \common_report_Report
     */
    public function edit($instance, $form)
    {
        \helpers_TimeOutHelper::setTimeOutLimit(\helpers_TimeOutHelper::LONG);
        try {

            $fileInfo = $form->getValue('source');
            $xmlFile = $this->getSharedStimulusFile($fileInfo['uploaded_file']);
            
            // throws an exception of invalid
            SharedStimulusImporter::isValidSharedStimulus($xmlFile);
            
            $embeddedFile = $this->embedAssets($xmlFile);
            $report = $this->replaceSharedStimulus(
                    $instance,
                    \tao_helpers_Uri::decode($form->getValue('lang')),
                    $embeddedFile
                );
        } catch (\Exception $e) {
            $report = \common_report_Report::createFailure($e->getMessage());
        }
        \helpers_TimeOutHelper::reset();
        return $report;
    }

    /**
     * Embed external resources into the XML
     *
     * @param string $originalXml
     * @throws \tao_models_classes_FileNotFoundException
     * @return string
     */
    public static function embedAssets($originalXml)
    {
        $basedir = dirname($originalXml).DIRECTORY_SEPARATOR;

        $xmlDocument = new XmlDocument();
        $xmlDocument->load($originalXml, true);

        //get images and object to base64 their src/data
        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');
        $objects = $xmlDocument->getDocumentComponent()->getComponentsByClassName('object');

        /** @var $image \qtism\data\content\xhtml\Img */
        foreach ($images as $image) {
            $source = $image->getSrc();
            if (file_exists($basedir . $source)) {
                $base64 = 'data:' . FsUtils::getMimeType($basedir . $source) . ';'
                    . 'base64,' . base64_encode(file_get_contents($basedir . $source));
                $image->setSrc($base64);
            } else {
                throw new \tao_models_classes_FileNotFoundException($source);
            }
        }

        /** @var $object \qtism\data\content\xhtml\Object */
        foreach ($objects as $object) {
            $data = $object->getData();
            if (file_exists($basedir . $data)) {
                $base64 = 'data:' . FsUtils::getMimeType($basedir . $data) . ';'
                    . 'base64,' . base64_encode(file_get_contents($basedir . $data));
                $object->setData($base64);
            } else {
                throw new \tao_models_classes_FileNotFoundException($data);
            }
        }

        // save the document to a tempfile
        $newXml = tempnam(sys_get_temp_dir(), 'sharedStimulus_');
        $xmlDocument->save($newXml);
        return $newXml;
    }

    /**
     * Get the shared stimulus file with assets from the zip
     * 
     * @param string $filePath path of the zip file
     * @return string path to the xml
     */
    private function getSharedStimulusFile($filePath)
    {
        $extractPath = $this->extractArchive($filePath);
    
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath),
            \RecursiveIteratorIterator::LEAVES_ONLY);
    
        /** @var $file \SplFileInfo */
        foreach ($iterator as $file) {
            //check each file to see if it can be the shared stimulus file
            if ($file->isFile()) {
                if (preg_match('/^[\w]/', $file->getFilename()) === 1 && $file->getExtension() === 'xml') {
                    return $file->getRealPath();
                }
            }
        }
    
        throw new \common_Exception('XML not found');
    }

    /**
     * Validate an xml file, convert file linked inside and store it into media manager
     * @param \core_kernel_classes_Resource $class the class under which we will store the shared stimulus (can be an item)
     * @param string $lang language of the shared stimulus
     * @param string $xmlFile File to store
     * @return \common_report_Report
     */
    protected function storeSharedStimulus($class, $lang, $xmlFile)
    {
        SharedStimulusImporter::isValidSharedStimulus($xmlFile);

        $name = basename($xmlFile, '.xml');
        $name .= '.xhtml';
        $filepath = dirname($xmlFile) . '/' . $name;
        \tao_helpers_File::copy($xmlFile, $filepath);

        $service = MediaService::singleton();
        if (!$service->createMediaInstance($filepath, $class->getUri(), $lang, basename($filepath))) {
            $report = \common_report_Report::createFailure(__('Fail to import Shared Stimulus'));
        } else {
            $report = \common_report_Report::createSuccess(__('Shared Stimulus imported successfully'));
        }

        return $report;
    }

    /**
     * Validate an xml file, convert file linked inside and store it into media manager
     * @param \core_kernel_classes_Resource $instance the instance to edit
     * @param string $lang language of the shared stimulus
     * @param string $xmlFile File to store
     * @return \common_report_Report
     */
    protected function replaceSharedStimulus($instance, $lang, $xmlFile)
    {
        //if the class does not belong to media classes create a new one with its name (for items)
        $mediaClass = new core_kernel_classes_Class(MEDIA_URI);
        if (!$instance->isInstanceOf($mediaClass)) {
            $report = \common_report_Report::createFailure(
                'The instance ' . $instance->getUri() . ' is not a Media instance'
            );
            return $report;
        }

        SharedStimulusImporter::isValidSharedStimulus($xmlFile);
        $name = basename($xmlFile, '.xml');
        $name .= '.xhtml';
        $filepath = dirname($xmlFile) . '/' . $name;
        \tao_helpers_File::copy($xmlFile, $filepath);

        $service = MediaService::singleton();
        if (!$service->editMediaInstance($filepath, $instance->getUri(), $lang)) {
            $report = \common_report_Report::createFailure(__('Fail to edit Shared Stimulus'));
        } else {
            $report = \common_report_Report::createSuccess(__('Shared Stimulus edited successfully'));
        }

        return $report;
    }
}
