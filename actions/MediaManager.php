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

namespace oat\taoMediaManager\actions;

use oat\taoMediaManager\model\fileManagement\FileManager;
use oat\taoMediaManager\model\MediaService;

class MediaManager extends \tao_actions_SaSModule {

    protected function getClassService()
    {
        return MediaService::singleton();
    }

	public function __construct(){
		
		parent::__construct();
		$this->service = $this->getClassService();
		//the service is initialized by default
		$this->defaultData();
	}

    /**
     * Show the form to edit an instance, show also a preview of the media
     */
    public function editInstance(){
        $clazz = $this->getCurrentClass();
        $instance = $this->getCurrentInstance();
        $myFormContainer = new \tao_actions_form_Instance($clazz, $instance);

        $myForm = $myFormContainer->getForm();
        if($myForm->isSubmited()){
            if($myForm->isValid()){

                $values = $myForm->getValues();
                // save properties
                $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($instance);
                $instance = $binder->bind($values);
                $message = __('Instance saved');

                $this->setData('message',$message);
                $this->setData('reload', true);
            }
        }

        $this->setData('formTitle', __('Edit Instance'));
        $this->setData('myForm', $myForm->render());
        $uri = ($this->hasRequestParameter('id'))?$this->getRequestParameter('id'):\tao_helpers_Uri::decode($this->getRequestParameter('uri'));
        $media = new \core_kernel_classes_Resource($uri);
        $link = $media->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK));
        if($link instanceof \core_kernel_classes_Literal){
            $link = $link->literal;
        }
        $fileManager = FileManager::getFileManagementModel();
        $filePath = $fileManager->retrieveFile($link);
        $fp = fopen($filePath, "r");
        $data = '';
        if ($fp !== false) {
            while (!feof($fp))
            {
                $data .= base64_encode(fread($fp, filesize($filePath)));
            }
            fclose($fp);
        }

        $this->setData('mimeType', \tao_helpers_File::getMimeType($filePath));
        $this->setData('base64Data', $data);
        $this->setView('form.tpl');

	}
}
