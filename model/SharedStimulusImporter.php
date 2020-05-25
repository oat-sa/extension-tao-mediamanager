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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model;

use oat\oatbox\filesystem\File;
use common_report_Report as Report;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\log\TaoLoggerAwareInterface;
use oat\oatbox\service\ConfigurableService;
use tao_helpers_form_Form as Form;
use oat\tao\model\import\ImportHandlerHelperTrait;
use oat\tao\model\import\TaskParameterProviderInterface;
use qtism\data\QtiComponent;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;
use tao_models_classes_import_ImportHandler;

/**
 * @access  public
 * @package taoMediaManager
 */
class SharedStimulusImporter extends ConfigurableService implements
    tao_models_classes_import_ImportHandler,
    TaskParameterProviderInterface,
    TaoLoggerAwareInterface
{
    use ImportHandlerHelperTrait {
        getTaskParameters as getDefaultTaskParameters;
    }
    use LoggerAwareTrait;

    /** @var SharedStimulusPackageImporter */
    private $zipImporter = null;

    /** @var string */
    private $instanceUri;

    public function setInstanceUri(string $instanceUri): self
    {
        $this->instanceUri = $instanceUri;

        return $this;
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
     * @return Form
     */
    public function getForm()
    {
        return (new FileImportForm($this->instanceUri))
            ->getForm();
    }

    /**
     * Starts the import based on the form
     *
     * @param \core_kernel_classes_Class $class
     * @param Form|array $form
     * @param string|null $userId owner of the resource
     * @return Report $report
     * @throws \common_exception_Error
     */
    public function import($class, $form, $userId = null)
    {
        $uploadedFile = $this->fetchUploadedFile($form);

        try {
            $service = $this->getMediaService();
            $classUri = $class->getUri();

            $instanceUri = $form instanceof Form
                ? $form->getValue('instanceUri')
                : (isset($form['instanceUri']) ? $form['instanceUri'] : null);

            $fileInfo = $form instanceof Form ? $form->getValue('source') : $form['source'];

            // importing new media
            if (!$instanceUri || $instanceUri === $classUri) {
                //if the file is a zip do a zip import
                if (!\helpers_File::isZipMimeType($fileInfo['type'])) {
                    try {
                        self::isValidSharedStimulus($uploadedFile);

                        $mediaResourceUri = $service->createMediaInstance(
                            $uploadedFile,
                            $classUri,
                            \tao_helpers_Uri::decode($form instanceof Form ? $form->getValue('lang') : $form['lang']),
                            $fileInfo['name'],
                            'application/qti+xml',
                            $userId
                        );

                        if (!$mediaResourceUri) {
                            $report = Report::createFailure(__('Fail to import Shared Stimulus'));
                            $report->setData(['uriResource' => '']);
                        } else {
                            $report = Report::createSuccess(__('Shared Stimulus imported successfully'));
                            $report->add(Report::createSuccess(
                                __('Imported %s', $fileInfo['name']),
                                ['uriResource' => $mediaResourceUri] // 'uriResource' key is needed by javascript in tao/views/templates/form/import.tpl
                            ));
                        }
                    } catch (XmlStorageException $e) {
                        // The shared stimulus is not qti compliant, display error
                        $report = Report::createFailure($e->getMessage());
                        $report->setData(['uriResource' => '']);
                    }
                } else {
                    $report = $this->getZipImporter()->import($class, $form, $userId);
                }
            } else {
                if (!\helpers_File::isZipMimeType($fileInfo['type'])) {
                    self::isValidSharedStimulus($uploadedFile);
                    if (in_array($fileInfo['type'], ['application/xml', 'text/xml'])) {
                        $name = basename($fileInfo['name'], 'xml');
                        $name .= 'xhtml';
                        $filepath = \tao_helpers_File::concat([dirname($fileInfo['name']), $name]);
                        $fileResource = fopen($filepath, 'w');
                        $uploadedFileResource = $uploadedFile->readStream();
                        stream_copy_to_stream($uploadedFileResource, $fileResource);
                        fclose($fileResource);
                        fclose($uploadedFileResource);
                    }

                    if (
                        !$service->editMediaInstance(
                            isset($filepath) ? $filepath : $uploadedFile,
                            $instanceUri,
                            \tao_helpers_Uri::decode($form instanceof Form ? $form->getValue('lang') : $form['lang']),
                            $userId
                        )
                    ) {
                        $report = Report::createFailure(__('Fail to edit shared stimulus'));
                    } else {
                        $report = Report::createSuccess(__('Shared Stimulus edited successfully'));
                    }

                    $report->setData(['uriResource' => $instanceUri]);
                } else {
                    $report = $this->getZipImporter()->edit(new \core_kernel_classes_Resource($instanceUri), $form, $userId);
                }
            }
        } catch (\Exception $e) {
            $message = $e instanceof \common_exception_UserReadableException
                ? $e->getUserMessage()
                : __('An error has occurred. Please contact your administrator.');
            $report = Report::createFailure($message);
            $report->setData(['uriResource' => '']);
            $this->logError($e->getMessage());
        }

        $this->getUploadService()->remove($uploadedFile);

        return $report;
    }

    /**
     * @param string|File $file
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
        $interactions = [
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

        ];

        return self::hasComponents($domDocument, $interactions);
    }

    /**
     * Check if the document contains feedback element
     *
     * @param QtiComponent $domDocument
     * @return bool
     */
    private static function hasFeedback(QtiComponent $domDocument)
    {
        $feedback = [
            'feedbackBlock',
            'feedbackInline'
        ];

        return self::hasComponents($domDocument, $feedback);
    }

    /**
     * Check if the document contains feedback element
     *
     * @param QtiComponent $domDocument
     * @return bool
     */
    private static function hasTemplate(QtiComponent $domDocument)
    {
        return self::hasComponents($domDocument, 'templateDeclaration');
    }

    /**
     * @param QtiComponent $domDocument
     * @param string|string[] $className
     * @return bool
     */
    private static function hasComponents(QtiComponent $domDocument, $className)
    {
        return $domDocument->getComponentsByClassName($className)->count() > 0;
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
            $this->zipImporter->setServiceLocator($this->getServiceLocator());
        }
        return $this->zipImporter;
    }

    /**
     * Defines the task parameters to be stored for later use.
     *
     * @param Form $form
     * @return array
     */
    public function getTaskParameters(Form $form)
    {
        return array_merge(
            $form->getValues(),
            $this->getDefaultTaskParameters($form)
        );
    }

    private function getMediaService(): MediaService
    {
        return $this->getServiceLocator()->get(MediaService::class);
    }
}
