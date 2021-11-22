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
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\parser;

use DOMDocument;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JsonQtiAttributeParserTest extends TestCase
{
    /** @var JsonQtiAttributeParser */
    private $subject;

    /** @var mixed|LoggerService|MockObject */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new JsonQtiAttributeParser();
        $this->subject->setLogger($this->logger);
    }

    public function testParseWithLanguage(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p2" xml:lang="es-MX"></div>';
        $sharedStimulus = new SharedStimulus('id', '', '', $body);
        $result = $this->subject->parse($sharedStimulus);
        $this->assertSame('es-MX', $result['attributes']['xml:lang']);
    }

    public function testParseWithoutLanguage(): void
    {
        $body = <<<XML_DOCUMENT
<?xml version="1.0" encoding="UTF-8"?>
<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p2"/>

XML_DOCUMENT;

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('lang attribute is empty. Impossible to set the Language Attribute', ['document' => $body]);

        $sharedStimulus = new SharedStimulus('id', '', '', $body);
        $result = $this->subject->parse($sharedStimulus);
        $this->assertArrayNotHasKey('xml:lang', (array) $result['attributes']);
    }
}
