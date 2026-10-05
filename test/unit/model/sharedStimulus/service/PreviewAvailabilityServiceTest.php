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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use oat\generis\test\TestCase;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\service\PreviewAvailabilityService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PreviewAvailabilityServiceTest extends TestCase
{
    private const URI = 'https://example.com/tao.rdf#shared-stimulus';

    private PreviewAvailabilityService $subject;

    private SharedStimulusRepository $repository;

    private JsonQtiAttributeParser $parser;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(SharedStimulusRepository::class);
        $this->parser = $this->createMock(JsonQtiAttributeParser::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new PreviewAvailabilityService();
        $this->subject->setLogger($this->logger);
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock([
                SharedStimulusRepository::class => $this->repository,
                JsonQtiAttributeParser::class => $this->parser,
            ])
        );
    }

    public function testHasPreviewContentReturnsTrueWhenBodyContainsContent(): void
    {
        $sharedStimulus = new SharedStimulus(self::URI, 'label', 'language');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sharedStimulus);

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->with($sharedStimulus)
            ->willReturn([
                'body' => [
                    'body' => '<p>Preview me</p>',
                ],
            ]);

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $this->assertTrue($this->subject->hasPreviewContent(self::URI));
    }

    public function testHasPreviewContentReturnsFalseWhenBodyIsWhitespace(): void
    {
        $sharedStimulus = new SharedStimulus(self::URI, 'label', 'language');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sharedStimulus);

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->with($sharedStimulus)
            ->willReturn([
                'body' => [
                    'body' => " \n\t ",
                ],
            ]);

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $this->assertFalse($this->subject->hasPreviewContent(self::URI));
    }

    public function testHasPreviewContentReturnsFalseWhenBodyNodeIsMissing(): void
    {
        $sharedStimulus = new SharedStimulus(self::URI, 'label', 'language');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sharedStimulus);

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->with($sharedStimulus)
            ->willReturn([
                'body' => '<p>Not the expected structure</p>',
            ]);

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $this->assertFalse($this->subject->hasPreviewContent(self::URI));
    }

    public function testHasPreviewContentReturnsFalseAndLogsWarningWhenParsingFails(): void
    {
        $sharedStimulus = new SharedStimulus(self::URI, 'label', 'language');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sharedStimulus);

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->with($sharedStimulus)
            ->willThrowException(new RuntimeException('Boom'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Unable to determine shared stimulus preview content'));

        $this->assertFalse($this->subject->hasPreviewContent(self::URI));
    }
}
