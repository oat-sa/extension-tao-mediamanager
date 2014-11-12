<?php
/*
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
 * Copyright (c) 2008-2010 (original work) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *
 */

namespace oat\taoMediaManager\model;

use core_kernel_classes_Class;
use oat\tao\helpers\FileUploadException;
use SebastianBergmann\Exporter\Exception;
use tao_helpers_form_Form;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager

 */
class FileImporter implements \tao_models_classes_import_ImportHandler
{


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
     * @param array $data the users tree selection
     * @return tao_helpers_form_Form
     */
    public function getForm()
    {
        $form = new FileImportForm();
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
            $service = MediaService::singleton();
            $service->createMediaInstance($file["uploaded_file"], \tao_helpers_Uri::decode($form->getValue('classUri')), \tao_helpers_Uri::decode($form->getValue('lang')));

            $report = \common_report_Report::createSuccess(__('Media imported successfully'));
            return $report;

        } catch(Exception $e){
            $report = \common_report_Report::createFailure($e->getMessage());
            return $report;
        }
    }
}
