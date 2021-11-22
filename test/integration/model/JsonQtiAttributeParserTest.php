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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\integration\model;

use LogicException;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use Psr\Log\LoggerInterface;

class JsonQtiAttributeParserTest extends TestCase
{
    /** @var JsonQtiAttributeParser */
    private $subject;

    /** @var Ontology|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    public function setUp(): void
    {
        $this->subject = new JsonQtiAttributeParser();
        $this->subject->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testRendererEmptyBody()
    {
        $this->expectException(LogicException::class);
        $sharedStimulus = new SharedStimulus('id', '', '', '');

        $this->assertEmpty($this->subject->parse($sharedStimulus));
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

        $attributes = $this->subject->parse($sharedStimulus);

        $this->assertArrayHasKey('qtiClass', $attributes);
        $this->assertSame('include', $attributes['qtiClass']);

        $this->assertArrayHasKey('body', $attributes);
        $this->assertArrayHasKey('body', $attributes['body']);

        return trim($attributes['body']['body']);
    }
}
