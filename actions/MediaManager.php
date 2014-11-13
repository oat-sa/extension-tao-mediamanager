<?php
/**
 * Docs Controller provide actions to manage docs
 * 
 * @author Bertrand Chevrier, <taosupport@tudor.lu>
 * @package taoMediaManager
 * @subpackage actions
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
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

    public function import(){




    }

	public function editInstance(){
        parent::editInstance();

        $uri = ($this->hasRequestParameter('id'))?$this->getRequestParameter('id'):\tao_helpers_Uri::decode($this->getRequestParameter('uri'));

        $media = new \core_kernel_classes_Resource($uri);
        $fileManager = new SimpleFileManagement();
        $filePath = $fileManager->retrieveFile($media->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK)));

        $fp = fopen($filePath, "r");
        if ($fp !== false) {
            $test =  '<embed height="100px" src="data:'.\tao_helpers_File::getMimeType($filePath).';base64,';
            while (!feof($fp))
            {
                $test .= base64_encode(fread($fp, filesize($filePath)));
            }
            $test .= '"/>';
            fclose($fp);
        }
        echo $test;
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
