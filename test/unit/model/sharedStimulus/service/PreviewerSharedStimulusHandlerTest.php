<?php

/**
 * SPDX-FileCopyrightText: 2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use common_exception_Error;
use core_kernel_classes_Resource;
use GuzzleHttp\Psr7\Utils;
use oat\taoMediaManager\model\sharedStimulus\css\dto\LoadStylesheet;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadStylesheetService;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\service\PreviewerSharedStimulusHandler;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use PHPUnit\Framework\TestCase;

class PreviewerSharedStimulusHandlerTest extends TestCase
{
    private SharedStimulusRepository $sharedStimulusRepository;

    private JsonQtiAttributeParser $sharedStimulusAttributesParser;

    private SharedStimulusResourceSpecification $sharedStimulusResourceSpecification;

    private ListStylesheetsService $listStylesheetsService;

    private LoadStylesheetService $loadStylesheetService;

    private PreviewerSharedStimulusHandler $subject;

    protected function setUp(): void
    {
        $this->sharedStimulusRepository = $this->createMock(SharedStimulusRepository::class);
        $this->sharedStimulusAttributesParser = $this->createMock(JsonQtiAttributeParser::class);
        $this->sharedStimulusResourceSpecification = $this->createMock(SharedStimulusResourceSpecification::class);
        $this->listStylesheetsService = $this->createMock(ListStylesheetsService::class);
        $this->loadStylesheetService = $this->createMock(LoadStylesheetService::class);

        $this->subject = new PreviewerSharedStimulusHandler(
            $this->sharedStimulusRepository,
            $this->sharedStimulusAttributesParser,
            $this->sharedStimulusResourceSpecification,
            $this->listStylesheetsService,
            $this->loadStylesheetService
        );
    }

    public function testBuildResponseFallsBackWhenParsedBodyShapeIsInvalid(): void
    {
        $item = $this->mockItem('https://example.com/without-hash');
        $sharedStimulus = new SharedStimulus('https://example.com/without-hash', 'Shared passage', 'en-US');
        $identifier = md5('https://example.com/without-hash');

        $this->sharedStimulusRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($sharedStimulus);

        $this->sharedStimulusAttributesParser
            ->expects($this->once())
            ->method('parse')
            ->with($sharedStimulus)
            ->willReturn([
                'body' => '<p>wrong shape</p>',
            ]);

        $this->listStylesheetsService
            ->expects($this->once())
            ->method('getList')
            ->willReturn([]);

        $result = $this->subject->buildResponse($item, 'base-url');

        $this->assertSame(
            [
                'serial' => 'container_' . $identifier,
                'body' => '',
                'elements' => [],
            ],
            $result['content']['data']['body']
        );
        $this->assertSame(
            'response_container_' . $identifier,
            $result['content']['data']['responseProcessing']['serial']
        );
    }

    public function testLoadAssetStreamRejectsUnsafePath(): void
    {
        $item = $this->mockItem('https://example.com/tao.rdf#i123');

        $this->loadStylesheetService
            ->expects($this->never())
            ->method('load');

        $this->expectException(common_exception_Error::class);
        $this->expectExceptionMessage('Invalid path "../unsafe.css"');

        $this->subject->loadAssetStream($item, '../unsafe.css');
    }

    public function testLoadAssetStreamLoadsStylesheetForCssPath(): void
    {
        $item = $this->mockItem('https://example.com/tao.rdf#i123');

        $this->loadStylesheetService
            ->expects($this->once())
            ->method('load')
            ->with($this->callback(function (LoadStylesheet $query): bool {
                return $query->getUri() === 'https://example.com/tao.rdf#i123'
                    && $query->getStylesheetUri() === 'tao-user-styles.css';
            }))
            ->willReturn(Utils::streamFor('css-body'));

        $this->assertNotNull($this->subject->loadAssetStream($item, 'css/tao-user-styles.css'));
    }

    private function mockItem(string $uri): core_kernel_classes_Resource
    {
        $item = $this->createMock(core_kernel_classes_Resource::class);
        $item->method('getUri')->willReturn($uri);

        return $item;
    }
}
