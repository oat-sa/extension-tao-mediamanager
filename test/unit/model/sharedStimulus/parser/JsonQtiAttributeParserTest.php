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

use common_session_Session;
use oat\generis\test\TestCase;
use oat\oatbox\session\SessionService;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use PHPUnit\Framework\MockObject\MockObject;

class JsonQtiAttributeParserTest extends TestCase
{
    /** @var JsonQtiAttributeParser */
    private $subject;

    /** @var SessionService|MockObject */
    private $sessionServiceMock;

    /** @var common_session_Session|MockObject */
    private $sessionMock;

    public function setUp(): void
    {
        $this->sessionMock = $this->createMock(common_session_Session::class);
        $this->sessionServiceMock = $this->createMock(SessionService::class);
        $this->subject = new JsonQtiAttributeParser();
        $this->subject->setServiceLocator($this->getServiceLocatorMock([
            SessionService::class => $this->sessionServiceMock,
        ]));
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
        $this->sessionServiceMock->method('getCurrentSession')->willReturn($this->sessionMock);
        $this->sessionMock->method('getDataLanguage')->willReturn('PL-pl');
        $body = '<?xml version="1.0" encoding="UTF-8"?><div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p2"></div>';
        $sharedStimulus = new SharedStimulus('id', '', '', $body);
        $result = $this->subject->parse($sharedStimulus);
        $this->assertSame('PL-pl', $result['attributes']['xml:lang']);
    }
}
