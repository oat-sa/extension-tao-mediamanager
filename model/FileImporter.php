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

use common_report_Report as Report;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\log\TaoLoggerAwareInterface;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use tao_helpers_form_Form as Form;
use oat\tao\model\import\ImportHandlerHelperTrait;
use oat\tao\model\import\TaskParameterProviderInterface;
use oat\taoMediaManager\model\task\RefreshTextReaderInteractionQtiTask;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Service methods to manage the Media
 *
 * @access  public
 * @author  Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class FileImporter implements
    \tao_models_classes_import_ImportHandler,
    ServiceLocatorAwareInterface,
    TaskParameterProviderInterface,
    TaoLoggerAwareInterface
{
    use ImportHandlerHelperTrait {
        getTaskParameters as getDefaultTaskParameters;
    }
    use LoggerAwareTrait;

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
        return __('File');
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
     * @param \core_kernel_classes_Class $class
     * @param Form|array $form
     * @param string|null $userId owner of the resource
     * @return Report
     * @throws \common_exception_Error
     */
    public function import($class, $form, $userId = null)
    {
        $uploadedFile = null;

        try {
            $uploadedFile = $this->fetchUploadedFile($form);
            $service = MediaService::singleton();
            $classUri = $class->getUri();

            if (!$form instanceof Form && !is_array($form)) {
                throw new \InvalidArgumentException('Import form should be either a Form object or an array.');
            }

            $instanceUri = $form instanceof Form
                ? $form->getValue('instanceUri')
                : (isset($form['instanceUri']) ? $form['instanceUri'] : null);

            $fileInfo = $form instanceof Form
                ? $form->getValue('source')
                : $form['source'];

            // importing new media
            if (!$instanceUri || $instanceUri === $classUri) {
                //if the file is a zip do a zip import
                if (!\helpers_File::isZipMimeType($fileInfo['type'])) {
                    $mediaResourceUri = $service->createMediaInstance(
                        $uploadedFile,
                        $classUri,
                        \tao_helpers_Uri::decode($form instanceof Form ? $form->getValue('lang') : $form['lang']),
                        $fileInfo['name'],
                        null,
                        $userId
                    );

                    if (!$mediaResourceUri) {
                        $report = Report::createFailure(__('Fail to import media'));
                        $report->setData(['uriResource' => '']);
                    } else {
                        $report = Report::createSuccess(__('Media imported successfully'));
                        $report->add(Report::createSuccess(
                            __('Imported %s', $fileInfo['name']),
                            // 'uriResource' key is needed by javascript in tao/views/templates/form/import.tpl
                            ['uriResource' => $mediaResourceUri]
                        ));

                        $misdetectedMime = $this->getMisdetectedStylesheetMime(
                            $uploadedFile,
                            (string) $fileInfo['name']
                        );

                        if ($misdetectedMime !== null) {
                            $report->add(new Report(
                                Report::TYPE_WARNING,
                                __(
                                    'The stylesheet "%s" is stored as text/css, but its content was detected as "%s". '
                                    . 'Content sniffing is unreliable for CSS: remove markup such as <style> '
                                    . 'or <title> from the file, otherwise other tools may treat it as HTML.',
                                    $fileInfo['name'],
                                    $misdetectedMime
                                )
                            ));
                        }
                    }
                } else {
                    $report = $this->getZipImporter()->import($class, $form, $userId);
                }
            } else {
                // editing existing media
                if (!\helpers_File::isZipMimeType($fileInfo['type'])) {
                    $service->editMediaInstance(
                        $uploadedFile,
                        $instanceUri,
                        \tao_helpers_Uri::decode($form instanceof Form ? $form->getValue('lang') : $form['lang']),
                        $userId
                    );
                    $this->scheduleTextReaderInteractionRefresh($instanceUri);
                    $report = Report::createSuccess(__('Media imported successfully'));
                    $report->add(Report::createSuccess(
                        __('Edited %s', $fileInfo['name']),
                        // 'uriResource' key is needed by javascript in tao/views/templates/form/import.tpl
                        ['uriResource' => $instanceUri]
                    ));
                } else {
                    $report = Report::createFailure(__('You can\'t upload a zip file as a media'));
                    $report->setData(['uriResource' => $instanceUri]);
                }
            }
        } catch (\Exception $e) {
            $message = $e instanceof \common_exception_UserReadableException
                ? $e->getUserMessage()
                : __('An error has occurred. Please contact your administrator.');
            $report = Report::createFailure($message);
            $this->logError($e->getMessage());
            $report->setData(['uriResource' => '']);
        }

        if ($uploadedFile !== null) {
            $this->getUploadService()->remove($uploadedFile);
        }

        return $report;
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

    /**
     * Get the zip importer for shared stimulus
     *
     * @return SharedStimulusPackageImporter
     */
    protected function getZipImporter()
    {
        $zipImporter = new SharedStimulusPackageImporter();
        $zipImporter->setServiceLocator($this->getServiceLocator());
        return $zipImporter;
    }

    private function scheduleTextReaderInteractionRefresh(string $instanceUri): void
    {
        try {
            $task = $this->getQueueDispatcher()->createTask(
                new RefreshTextReaderInteractionQtiTask(),
                [
                    RefreshTextReaderInteractionQtiTask::PARAM_MEDIA_ID => $instanceUri,
                ],
                __('Import media files')
            );
            $this->logInfo(
                sprintf(
                    'Queued Text Reader qti.xml refresh for media "%s" in task "%s"',
                    $instanceUri,
                    $task->getId()
                )
            );
        } catch (\Throwable $throwable) {
            $this->logError(
                sprintf(
                    'Unable to queue Text Reader qti.xml refresh for media "%s": %s',
                    $instanceUri,
                    $throwable->getMessage()
                )
            );
        }
    }

    private function getQueueDispatcher(): QueueDispatcherInterface
    {
        return $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
    }

    /**
     * Content based MIME detection reports stylesheets as text/html when their text mentions an HTML tag,
     * so the importer has to tell the user when that happens.
     *
     * @param string|\oat\oatbox\filesystem\File $uploadedFile
     */
    private function getMisdetectedStylesheetMime($uploadedFile, string $fileName): ?string
    {
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'css') {
            return null;
        }

        $mimeType = $uploadedFile instanceof \oat\oatbox\filesystem\File
            ? (string) $uploadedFile->getMimeType()
            : (string) \tao_helpers_File::getMimeType($uploadedFile);

        return $mimeType === 'text/css' ? null : ($mimeType === '' ? 'unknown' : $mimeType);
    }
}
