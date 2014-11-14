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
class ZipImportForm extends \tao_helpers_form_FormContainer
{

    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('export');
        $submitElt = \tao_helpers_form_FormFactory::getElement('import', 'Free');
        $submitElt->setValue('<a href="#" class="form-submitter btn-success small"><span class="icon-import"></span> ' .__('Import').'</a>');

        $this->form->setActions(array($submitElt), 'bottom');
        $this->form->setActions(array(), 'top');
    }

    /**
     * Used to create the form elements and bind them to the form instance
     *
     * @access protected
     * @author Cédric Alfonsi, <cedric.alfonsi@tudor.lu>
     * @return mixed
     */
    protected function initElements()
    {
        //create file upload form box
        $fileElt = \tao_helpers_form_FormFactory::getElement('source', 'AsyncFile');
        $fileElt->setDescription(__("Add an archive file (.zip)"));
        if(isset($_POST['import_sent_zip'])){
            $fileElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
        }
        else{
            $fileElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty', array('message' => '')));
        }
        $fileElt->addValidators(array(
//                \tao_helpers_form_FormFactory::getValidator('FileMimeType', array('mimetype' => array('text/xml', 'application/rdf+xml', 'application/xml'), 'extension' => array('rdf', 'rdfs'))),
                \tao_helpers_form_FormFactory::getValidator('FileSize', array('max' => \tao_helpers_Environment::getFileUploadLimit()))
            ));

        $this->form->addElement($fileElt);
        $this->form->createGroup('file', __('Import Media from ZIP file'), array('zip_desc', 'source'));

        $zipSentElt = \tao_helpers_form_FormFactory::getElement('import_sent_zip', 'Hidden');
        $zipSentElt->setValue(1);
        $this->form->addElement($zipSentElt);

        $dataUsage = new \core_kernel_classes_Resource(INSTANCE_LANGUAGE_USAGE_DATA);
        $langService = \tao_models_classes_LanguageService::singleton();

        $langOptions = array();
        foreach($langService->getAvailableLanguagesByUsage($dataUsage) as $lang){
            $langOptions[\tao_helpers_Uri::encode($lang->getUri())] = $lang->getLabel();
        }

        $langElt = \tao_helpers_form_FormFactory::getElement('lang', 'Combobox');
        $langElt->setOptions($langOptions);
        $this->form->addElement($langElt);


        $this->form->createGroup('options', __('Media Options'), array(
                $langElt
            ));
    }
}
