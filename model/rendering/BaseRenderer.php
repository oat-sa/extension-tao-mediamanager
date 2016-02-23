<?php
/**
 * Created by Antoine on 03/02/2016
 * at 13:12
 */

namespace oat\taoMediaManager\model\rendering;


use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\MediaRendererInterface;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileManager;
use oat\taoMediaManager\model\MediaSource;

class BaseRenderer extends ConfigurableService implements MediaRendererInterface
{

    private $xml;

    public function __construct(array $options = array())
    {
        $this->xml = false;
        parent::__construct($options);
    }

    public function render($mediaLink)
    {
        $mediaSource = new MediaSource(array());
        $fileInfo = $mediaSource->getFileInfo($mediaLink);
        $link = $fileInfo['link'];

        $fileManagement = $this->getServiceManager()->get(FileManagement::SERVICE_ID);

        if($this->isXml()){
            header(\HTTPToolkit::statusCodeHeader(200));
            \Context::getInstance()->getResponse()->setContentHeader('application/json');
            echo json_encode(htmlentities((string)$fileManagement->getFileStream($link)));
        }
        else{
            \tao_helpers_Http::returnStream($fileManagement->getFileStream($link), $fileManagement->getFileSize($link), $fileInfo['mime']);
        }
    }

    /**
     * @param Boolean $xml
     */
    public function setXml($xml = true){
        $this->xml = $xml;
    }

    public function isXml(){
        return $this->xml;
    }
}