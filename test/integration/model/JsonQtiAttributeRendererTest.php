<?php


namespace oat\taoMediaManager\test\integration\model;


use LogicException;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\sharedStimulus\renderer\JsonQtiAttributeRenderer;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;

class JsonQtiAttributeRendererTest extends TestCase
{
    public function testRendererEmptyBody()
    {
        $this->expectException(LogicException::class);
        $sharedStimulus = new SharedStimulus('id', '', '', '');
        $renderer = new JsonQtiAttributeRenderer();

        $this->assertEmpty($renderer->render($sharedStimulus));
    }

    public function testRenderSimpleSharedStimulus()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" class="stimulus_content" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_v2p1.xsd">
    <p>Lorem ip sum</p>
</div>
';
        $body = $this->renderXmlBody($xml);
        $this->assertSame('<p>Lorem ip sum</p>', trim($body));
    }

    private function renderXmlBody($xml)
    {
        $sharedStimulus = new SharedStimulus('id', '', '', $xml);
        $renderer = new JsonQtiAttributeRenderer();

        $attributes = $renderer->render($sharedStimulus);

        $this->assertArrayHasKey('qtiClass', $attributes);
        $this->assertSame('include', $attributes['qtiClass']);

        $this->assertArrayHasKey('body', $attributes);
        $this->assertArrayHasKey('body', $attributes['body']);

        return trim($attributes['body']['body']);
    }


}