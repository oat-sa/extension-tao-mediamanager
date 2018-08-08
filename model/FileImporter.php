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
use tao_helpers_form_Form as Form;
use oat\tao\model\import\ImportHandlerHelperTrait;
use oat\tao\model\import\TaskParameterProviderInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Service methods to manage the Media
 *
 * @access  public
 * @author  Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class FileImporter implements \tao_models_classes_import_ImportHandler, ServiceLocatorAwareInterface, TaskParameterProviderInterface
{
    use ImportHandlerHelperTrait { getTaskParameters as getDefaultTaskParameters; }

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
     * @return Report
     * @throws \common_exception_Error
     */
    public function import($class, $form)
    {
        $uploadedFile = $this->fetchUploadedFile($form);

        try {
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
                        $fileInfo['name']
                    );

                    if (!$mediaResourceUri) {
                        $report = Report::createFailure(__('Fail to import media'));
                        $report->setData(['uriResource' => '']);
                    } else {
                        $report = Report::createSuccess(__('Media imported successfully'));
                        $report->add(Report::createSuccess(
                            __('Imported %s', $fileInfo['name']),
                            ['uriResource' => $mediaResourceUri] // 'uriResource' key is needed by javascript in tao/views/templates/form/import.tpl
                        ));
                    }
                } else {
                    $zipImporter = new ZipImporter();
                    $zipImporter->setServiceLocator($this->getServiceLocator());
                    $report = $zipImporter->import($class, $form);
                }
            } else {
                // editing existing media
                if (!\helpers_File::isZipMimeType($fileInfo['type'])) {
                    $service->editMediaInstance(
                        $uploadedFile,
                        $instanceUri,
                        \tao_helpers_Uri::decode($form instanceof Form ? $form->getValue('lang') : $form['lang'])
                    );
                    $report = Report::createSuccess(__('Media imported successfully'));
                    $report->add(Report::createSuccess(
                        __('Edited %s', $fileInfo['name']),
                        ['uriResource' => $instanceUri] // 'uriResource' key is needed by javascript in tao/views/templates/form/import.tpl
                    ));
                } else {
                    $report = Report::createFailure(__('You can\'t upload a zip file as a media'));
                    $report->setData(['uriResource' => $instanceUri]);
                }
            }
        } catch (\Exception $e) {
            $report = Report::createFailure($e->getMessage());
            $report->setData(['uriResource' => '']);
        }

        $this->getUploadService()->remove($uploadedFile);

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
}
