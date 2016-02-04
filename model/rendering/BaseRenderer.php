<?php
/**
 * Created by Antoine on 03/02/2016
 * at 13:12
 */

namespace oat\taoMediaManager\model\rendering;


use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\MediaRendererInterface;

class BaseRenderer extends ConfigurableService implements MediaRendererInterface
{

    public function __construct(array $options = array())
    {
        parent::__construct($options);
    }

    public function render($mediaLink)
    {
        // TODO: Implement render() method.
    }
}