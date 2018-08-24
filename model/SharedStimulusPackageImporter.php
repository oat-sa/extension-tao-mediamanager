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

use core_kernel_classes_Resource as Resource;
use common_report_Report as Report;
use tao_helpers_form_Form as Form;
use core_kernel_classes_Class;
use qtism\data\storage\xml\XmlDocument;

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
     * @param Form|array $form
     * @return Report
     */
    public function import($class, $form)
    {
        try {
            $uploadedFile = $this->fetchUploadedFile($form);

            $xmlFile = $this->getSharedStimulusFile($uploadedFile);

            $this->getUploadService()->remove($uploadedFile);

            // throws an exception of invalid
            SharedStimulusImporter::isValidSharedStimulus($xmlFile);

            $embeddedFile = static::embedAssets($xmlFile);

            $report = Report::createSuccess(__('Shared Stimulus imported successfully'));

            $subReport = $this->storeSharedStimulus($class, $this->getDecodedUri($form), $embeddedFile);

            $report->add($subReport);
        } catch (\Exception $e) {
            $report = Report::createFailure($e->getMessage());
        }

        return $report;
    }

    /**
     * @param \core_kernel_classes_Resource $instance
     * @param Form|array $form
     * @return Report
     * @throws \common_exception_NotAcceptable
     */
    public function edit(Resource $instance, $form)
    {
        try {
            $uploadedFile = $this->fetchUploadedFile($form);

            $xmlFile = $this->getSharedStimulusFile($uploadedFile);

            $this->getUploadService()->remove($uploadedFile);

            // throws an exception of invalid
            SharedStimulusImporter::isValidSharedStimulus($xmlFile);

            $embeddedFile = static::embedAssets($xmlFile);

            $report = $this->replaceSharedStimulus($instance, $this->getDecodedUri($form), $embeddedFile);
        } catch (\Exception $e) {
            $report = Report::createFailure($e->getMessage());
            $report->setData(['uriResource' => '']);
        }

        return $report;
    }

    /**
     * Embed external resources into the XML
     *
     * @param $originalXml
     * @return string
     * @throws \common_exception_Error
     * @throws \qtism\data\storage\xml\XmlStorageException
     * @throws \tao_models_classes_FileNotFoundException
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
            $image->setSrc(self::secureEncode($basedir, $source));
        }

        /** @var $object \qtism\data\content\xhtml\Object */
        foreach ($objects as $object) {
            $data = $object->getData();
            $object->setData(self::secureEncode($basedir, $data));
        }

        // save the document to a tempfile
        $newXml = tempnam(sys_get_temp_dir(), 'sharedStimulus_').'.xml';
        $xmlDocument->save($newXml);
        return $newXml;
    }

    /**
     * Get the shared stimulus file with assets from the zip
     *
     * @param string $filePath path of the zip file
     * @return string path to the xml
     * @throws \common_Exception
     */
    private function getSharedStimulusFile($filePath)
    {
        \helpers_TimeOutHelper::setTimeOutLimit(\helpers_TimeOutHelper::LONG);
        $extractPath = $this->extractArchive($filePath);
        \helpers_TimeOutHelper::reset();

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
    
        throw new \common_Exception('XML not found in the package');
    }

    /**
     * Validate an xml file, convert file linked inside and store it into media manager
     *
     * @param \core_kernel_classes_Resource $class the class under which we will store the shared stimulus (can be an item)
     * @param string $lang language of the shared stimulus
     * @param string $xmlFile File to store
     * @return \common_report_Report
     *
     * @throws \qtism\data\storage\xml\XmlStorageException
     */
    protected function storeSharedStimulus($class, $lang, $xmlFile)
    {
        SharedStimulusImporter::isValidSharedStimulus($xmlFile);

        $service = MediaService::singleton();
        if ($mediaResourceUri = $service->createMediaInstance($xmlFile, $class->getUri(), $lang, basename($xmlFile), 'application/qti+xml')) {
            $report = Report::createSuccess(__('Imported %s', basename($xmlFile)));
            $report->setData(['uriResource' => $mediaResourceUri]);
        } else {
            $report = Report::createFailure(__('Fail to import Shared Stimulus'));
            $report->setData(['uriResource' => '']);
        }

        return $report;
    }

    /**
     * Validate an xml file, convert file linked inside and store it into media manager
     *
     * @param \core_kernel_classes_Resource $instance the instance to edit
     * @param string $lang language of the shared stimulus
     * @param string $xmlFile File to store
     * @return \common_report_Report
     *
     * @throws \qtism\data\storage\xml\XmlStorageException
     */
    protected function replaceSharedStimulus($instance, $lang, $xmlFile)
    {
        //if the class does not belong to media classes create a new one with its name (for items)
        $mediaClass = new core_kernel_classes_Class(MediaService::ROOT_CLASS_URI);
        if (!$instance->isInstanceOf($mediaClass)) {
            $report = Report::createFailure(
                'The instance ' . $instance->getUri() . ' is not a Media instance'
            );
            $report->setData(['uriResource' => '']);
            return $report;
        }

        SharedStimulusImporter::isValidSharedStimulus($xmlFile);
        $name = basename($xmlFile, '.xml');
        $name .= '.xhtml';
        $filepath = dirname($xmlFile) . '/' . $name;
        \tao_helpers_File::copy($xmlFile, $filepath);

        $service = MediaService::singleton();
        if (!$service->editMediaInstance($filepath, $instance->getUri(), $lang)) {
            $report = Report::createFailure(__('Fail to edit Shared Stimulus'));
        } else {
            $report = Report::createSuccess(__('Shared Stimulus edited successfully'));
        }

        $report->setData(['uriResource' => $instance->getUri()]);

        return $report;
    }
    
    /**
     * Verify paths and encode the file
     * 
     * @param string $basedir
     * @param string $source
     * @throws \tao_models_classes_FileNotFoundException
     * @throws \common_exception_Error
     * @return string
     */
    protected static function secureEncode($basedir, $source)
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
                throw new \common_exception_Error('Invalid source path "'.$source.'"');
            }
        } else {
            // url, just return it as is
            return $source;
        }
    }

    /**
     * @param array|Form $form
     * @return string
     */
    private function getDecodedUri($form)
    {
        return \tao_helpers_Uri::decode($form instanceof Form ? $form->getValue('lang') : $form['lang']);
    }
}
