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

use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\SimpleFileManagement;

class MediaManager extends \tao_actions_SaSModule {


    public function getOntologyData(){
        parent::getOntologyData();
    }

    protected function getClassService()
    {
        return MediaService::singleton();
    }


    /**
	 * constructor: initialize the service and the default data
	 * @return Docs
	 */
	public function __construct(){
		
		parent::__construct();
		$this->service = $this->getClassService();
		//the service is initialized by default
		$this->defaultData();
	}

	/**
	 * Show the list of documents
	 * @return void
	 */
	public function editMediaClass(){
        $clazz = new \core_kernel_classes_Class(\tao_helpers_Uri::decode($this->getRequestParameter('classUri')));


        $myForm = $this->editClass($clazz, $this->getRootClass());

        if($myForm->isSubmited()){
            if($myForm->isValid()){
                if($clazz instanceof \core_kernel_classes_Resource){
                    $this->setData("selectNode", \tao_helpers_Uri::encode($clazz->getUri()));
                }
                $this->setData('message', __('Class saved'));
                $this->setData('reload', true);
            }
        }
        $this->setData('formTitle', __('Edit Media class'));
        $this->setData('myForm', $myForm->render());
        $this->setView('form.tpl', 'tao');
    }

	public function editInstance(){
        parent::editInstance();

	}
		
	/**
	 * @see TaoModule::getRootClass
	 * @abstract implement the abstract method
	 */
	public function getRootClass(){
		return new \core_kernel_classes_Class(MEDIA_URI);
	}

}
?>
