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

use LogicException;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;

class JsonQtiAttributeParserTest extends TestCase
{
    /** @var JsonQtiAttributeParser */
    private $subject;

    public function setUp() :void
    {
        $this->subject = new JsonQtiAttributeParser();
    }

    public function testParse(): void
    {
        $id = 'fixture-id';
        $name = 'fixture-name';
        $language = 'fixture-language';

        $fixtureBodyImagePath = 'image/path.png';
        $pContent = '<p>any paragraph</p>';

        $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlBody .= '<div xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" >';
        $xmlBody .= '<img src="' . $fixtureBodyImagePath . '"/>';
        $xmlBody .= $pContent;
        $xmlBody .= '</div>';

        $sharedStimulus = new SharedStimulus($id, $name, $language, $xmlBody);
        $result = $this->subject->parse($sharedStimulus);

        $this->assertArrayHasKey('serial', $result);
        $this->assertArrayHasKey('qtiClass', $result);
        $this->assertEquals('include', $result['qtiClass']);
        $this->assertArrayHasKey('body', $result);

        $body = $result['body'];
        $this->assertArrayHasKey('serial', $body);
        $this->assertArrayHasKey('body', $body);
        $this->assertArrayHasKey('elements', $body);

        $elements = $body['elements'];

        $imageElement = reset($elements);
        $this->assertArrayHasKey('serial', $imageElement);
        $this->assertArrayHasKey('qtiClass', $imageElement);
        $this->assertEquals('img', $imageElement['qtiClass']);
        $this->assertArrayHasKey('attributes', $imageElement);

        $elementAttributes =  $imageElement['attributes'];
        $this->assertArrayHasKey('src', $elementAttributes);
        $this->assertEquals($fixtureBodyImagePath, $elementAttributes['src']);

        $bodyBody = $body['body'];
        $this->assertStringContainsString($pContent, $bodyBody);
    }

    public function testEmptySharedStimulusBody()
    {
        $id = 'fixture-id';
        $name = 'fixture-name';
        $language = 'fixture-language';

        $sharedStimulus = new SharedStimulus($id, $name, $language);

        $this->expectException(LogicException::class);
        $this->subject->parse($sharedStimulus);
    }
}
