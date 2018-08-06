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

use oat\oatbox\filesystem\File;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\upload\UploadService;
use qtism\data\QtiComponent;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;
use tao_helpers_form_Form;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @package taoMediaManager
 */
class SharedStimulusImporter implements \tao_models_classes_import_ImportHandler
{
    /**
     * @var SharedStimulusPackageImporter
     */
    private $zipImporter = null;
    private $instanceUri;

    public function __construct($instanceUri = null)
    {
        $this->instanceUri = $instanceUri;
    }

    /**
     * Returns a textual description of the import format
     *
     * @return string
     */
    public function getLabel()
    {
        return __('Shared Stimulus');
    }

    /**
     * Returns a form in order to prepare the import
     * if the import is from a file, the form should include the file element
     *
     * @return tao_helpers_form_Form
     */
    public function getForm()
    {
        $form = new FileImportForm($this->instanceUri);
        return $form->getForm();
    }

    /**
     * Starts the import based on the form
     *
     * @param \core_kernel_classes_Class $class
     * @param \tao_helpers_form_Form $form
     * @return \common_report_Report $report
     */
    public function import($class, $form)
    {
        //as upload may be called multiple times, we remove the session lock as soon as possible
        session_write_close();
        try {
            $file = $form->getValue('source');
            if (!isset($file['uploaded_file'])) {
                throw new \common_Exception('No source file for import');
            }

            /** @var  UploadService $uploadService */
            $uploadService = $this->getServiceLocator()->get(UploadService::SERVICE_ID);
            $uploadedFile = $uploadService->getUploadedFlyFile($file['uploaded_file']);

            $service = MediaService::singleton();
            $classUri = $class->getUri();

            if (is_null($this->instanceUri) || $this->instanceUri === $classUri) {


                //if the file is a zip do a zip import
                if (!\helpers_File::isZipMimeType($file['type'])) {
                    try {
                        self::isValidSharedStimulus($uploadedFile);
                        $name = $file['name'];
                        if (!$service->createMediaInstance($uploadedFile, $classUri,
                            \tao_helpers_Uri::decode($form->getValue('lang')), $name, 'application/qti+xml')
                        ) {
                            $report = \common_report_Report::createFailure(__('Fail to import Shared Stimulus'));
                        } else {
                            $report = \common_report_Report::createSuccess(__('Shared Stimulus imported successfully'));
                        }
                    } catch (XmlStorageException $e) {
                        // The shared stimulus is not qti compliant, display error
                        $report = \common_report_Report::createFailure($e->getMessage());
                    }
                } else {
                    $report = $this->getZipImporter()->import($class, $form);
                }
            } else {
                if (!\helpers_File::isZipMimeType($file['type'])) {
                    self::isValidSharedStimulus($uploadedFile);
                    if (in_array($file['type'], array('application/xml', 'text/xml'))) {
                        $name = basename($file['name'], 'xml');
                        $name .= 'xhtml';
                        $filepath = \tao_helpers_File::concat([dirname($file['name']), $name]);
                        $fileResource = fopen($filepath, 'w');
                        $uploadedFileResource = $uploadedFile->readStream();
                        stream_copy_to_stream($uploadedFileResource, $fileResource);
                        fclose($fileResource);
                        fclose($uploadedFileResource);
                    }
                    if (!$service->editMediaInstance($filepath, $this->instanceUri, \tao_helpers_Uri::decode($form->getValue('lang')))) {
                        $report = \common_report_Report::createFailure(__('Fail to edit shared stimulus'));
                    } else {
                        $report = \common_report_Report::createSuccess(__('Shared Stimulus edited successfully'));
                    }
                } else {
                    $report = $this->getZipImporter()->edit(new \core_kernel_classes_Resource($this->instanceUri), $form);
                }
            }

            $uploadService->remove($uploadedFile);

        } catch (\Exception $e) {
            $report = \common_report_Report::createFailure($e->getMessage());
        }

        return $report;
    }

    /**
     * @param $file
     * @return XmlDocument
     * @throws \qtism\data\storage\xml\XmlStorageException
     */
    public static function isValidSharedStimulus($file)
    {
        // No $version given = auto detect.
        $xmlDocument = new XmlDocument();

        // don't validate because of APIP
        if ($file instanceof File) {
            $xmlDocument->loadFromString($file->read(), false);
        } elseif (is_file($file) && is_readable($file)) {
            $xmlDocument->load($file, false);
        }


        // The shared stimulus is qti compliant, see if it is not an interaction, feedback or template
        if (self::hasInteraction($xmlDocument->getDocumentComponent())) {
            throw new XmlStorageException("The shared stimulus contains interactions QTI components.");
        }
        if (self::hasFeedback($xmlDocument->getDocumentComponent())) {
            throw new XmlStorageException("The shared stimulus contains feedback QTI components.");
        }

        if (self::hasTemplate($xmlDocument->getDocumentComponent())) {
            throw new XmlStorageException("The shared stimulus contains template QTI components.");
        }

        return $xmlDocument;
    }

    /**
     * Check if the document contains interactions element
     *
     * @param QtiComponent $domDocument
     * @return bool
     */
    private static function hasInteraction(QtiComponent $domDocument)
    {
        $interactions = array(
            'endAttemptInteraction',
            'inlineChoiceInteraction',
            'textEntryInteraction',
            'associateInteraction',
            'choiceInteraction',
            'drawingInteraction',
            'extendedTextInteraction',
            'gapMatchInteraction',
            'graphicAssociateInteraction',
            'graphicGapMatchInteraction',
            'graphicOrderInteraction',
            'hotspotInteraction',
            'selectPointInteraction',
            'hottextInteraction',
            'matchInteraction',
            'mediaInteraction',
            'orderInteraction',
            'sliderInteraction',
            'uploadInteraction',
            'customInteraction',
            'positionObjectInteraction',

        );
        return self::hasComponents($domDocument, $interactions);
    }

    /**
     * Check if the document contains feedback element
     * @param QtiComponent $domDocument
     * @return bool
     */
    private static function hasFeedback(QtiComponent $domDocument)
    {
        $feedback = array(
            'feedbackBlock',
            'feedbackInline'
        );
        return self::hasComponents($domDocument, $feedback);
    }

    /**
     * Check if the document contains feedback element
     * @param QtiComponent $domDocument
     * @return bool
     */
    private static function hasTemplate(QtiComponent $domDocument)
    {
        $templates = 'templateDeclaration';
        return self::hasComponents($domDocument, $templates);
    }

    /**
     * @param QtiComponent $domDocument
     * @param $className array of string or string
     * @return bool
     */
    private static function hasComponents(QtiComponent $domDocument, $className)
    {
        $components = $domDocument->getComponentsByClassName($className);
        return $components->count() > 0;
    }

    /**
     * @param SharedStimulusPackageImporter $zipImporter
     * @return $this
     */
    public function setZipImporter($zipImporter)
    {
        $this->zipImporter = $zipImporter;
        return $this;
    }

    /**
     * Get the zip importer for shared stimulus
     *
     * @return SharedStimulusPackageImporter
     */
    protected function getZipImporter()
    {
        if (!$this->zipImporter) {
            $this->zipImporter = new SharedStimulusPackageImporter();
        }
        return $this->zipImporter;
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
