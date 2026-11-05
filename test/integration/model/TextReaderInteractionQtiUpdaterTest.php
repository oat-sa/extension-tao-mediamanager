<?php

/**
 * SPDX-FileCopyrightText: 2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\integration\model;

use core_kernel_classes_Resource;
use GuzzleHttp\Psr7\Utils;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\service\ApplicationService;
use oat\tao\model\media\MediaService as TaoMediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\TextReaderReferencesExtractorAdapter;
use oat\taoMediaManager\model\TextReaderInteractionQtiUpdater;
use oat\taoQtiItem\model\qti\ResponseDeclaration;
use oat\taoQtiItem\model\qti\event\UpdatedItemEventDispatcher;
use oat\taoQtiItem\model\qti\interaction\PortableCustomInteraction;
use oat\taoQtiItem\model\qti\Item;
use oat\taoQtiItem\model\qti\parser\TextReaderReferencesExtractor;
use oat\taoQtiItem\model\qti\Service as QtiService;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use tao_helpers_Uri;

class TextReaderInteractionQtiUpdaterTest extends TestCase
{
    private const LANGUAGE = 'en-US';
    private const ITEM_URI = 'http://example.com/ontologies/tao.rdf#textReaderItem';
    private const ITEM_IDENTIFIER = 'item-1';
    private const MEDIA_ID = 'http://example.com/ontologies/tao.rdf#media';

    private ?string $tempImagePath = null;
    private ?ApplicationService $originalApplicationService = null;
    private ?TaoMediaService $originalTaoMediaService = null;

    protected function setUp(): void
    {
        $this->cleanupTemporaryImageArtifacts();

        if (!defined('PRODUCT_NAME')) {
            define('PRODUCT_NAME', 'TAO');
        }

        $serviceManager = ServiceManager::getServiceManager();
        if ($serviceManager->has(TaoMediaService::SERVICE_ID)) {
            $this->originalTaoMediaService = $serviceManager->get(TaoMediaService::SERVICE_ID);
        }

        if ($serviceManager->has(ApplicationService::SERVICE_ID)) {
            $this->originalApplicationService = $serviceManager->get(ApplicationService::SERVICE_ID);
        } else {
            $applicationService = $this->createMock(ApplicationService::class);
            $applicationService->method('getPlatformVersion')
                ->willReturn('test-version');
            $serviceManager->overload(ApplicationService::SERVICE_ID, $applicationService);
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalTaoMediaService !== null) {
            ServiceManager::getServiceManager()->overload(TaoMediaService::SERVICE_ID, $this->originalTaoMediaService);
        }

        if ($this->originalApplicationService !== null) {
            ServiceManager::getServiceManager()->overload(
                ApplicationService::SERVICE_ID,
                $this->originalApplicationService
            );
        }

        if ($this->tempImagePath !== null && file_exists($this->tempImagePath)) {
            unlink($this->tempImagePath);
        }

        $this->cleanupTemporaryImageArtifacts();
    }

    public function testRefreshByMediaIdUpdatesTextReaderContentAfterAssetReplacement(): void
    {
        $this->tempImagePath = $this->createTemporaryImage('Brazil.png');
        $mediaLink = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(self::MEDIA_ID);
        $contentPropertyKey = 'content-' . $mediaLink;
        $item = $this->createTextReaderItem($mediaLink);
        $itemResource = new core_kernel_classes_Resource(self::ITEM_URI);
        $mediaSource = $this->createMock(MediaSource::class);
        $mediaSource->expects($this->exactly(2))
            ->method('getFileInfo')
            ->with(tao_helpers_Uri::encode(self::MEDIA_ID))
            ->willReturn(['mime' => 'image/png']);
        $mediaSource->expects($this->exactly(2))
            ->method('getFileStream')
            ->with(tao_helpers_Uri::encode(self::MEDIA_ID))
            ->willReturnCallback(fn () => Utils::streamFor((string) file_get_contents($this->tempImagePath)));
        $taoMediaService = new TaoMediaService([
            TaoMediaService::OPTION_SOURCE => [
                'mediamanager' => $mediaSource,
            ],
        ]);
        $taoMediaService->setLogger(new NullLogger());
        ServiceManager::getServiceManager()->propagate($taoMediaService);
        ServiceManager::getServiceManager()->overload(TaoMediaService::SERVICE_ID, $taoMediaService);
        $resourceMatchesItemUri = fn (
            core_kernel_classes_Resource $resource
        ): bool => $resource->getUri() === self::ITEM_URI;

        $qtiService = $this->createMock(QtiService::class);
        $qtiService->expects($this->exactly(2))
            ->method('getDataItemByRdfItem')
            ->with($this->callback($resourceMatchesItemUri))
            ->willReturn($item);
        $qtiService->expects($this->exactly(2))
            ->method('saveDataItemToRdfItem')
            ->with(
                $item,
                $this->callback($resourceMatchesItemUri)
            );

        $eventDispatcher = $this->createMock(UpdatedItemEventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $item,
                $this->callback($resourceMatchesItemUri)
            );

        $subject = new TextReaderInteractionQtiUpdater(
            $this->createMock(MediaRelationRepositoryInterface::class),
            $qtiService,
            $eventDispatcher,
            new TextReaderReferencesExtractorAdapter(new TextReaderReferencesExtractor())
        );
        $subject->setLogger(new NullLogger());
        $method = new \ReflectionMethod(TextReaderInteractionQtiUpdater::class, 'refreshItemResource');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($subject, $itemResource, self::MEDIA_ID));
        $this->assertSame(
            $this->buildExpectedDataUrl($this->tempImagePath),
            $this->getTextReaderInteraction($item)->getProperties()[$contentPropertyKey]
        );

        $this->tempImagePath = $this->createTemporaryImage('Italy.png', $this->tempImagePath);

        $this->assertTrue($method->invoke($subject, $itemResource, self::MEDIA_ID));
        $this->assertSame(
            $this->buildExpectedDataUrl($this->tempImagePath),
            $this->getTextReaderInteraction($item)->getProperties()[$contentPropertyKey]
        );
    }

    private function createTemporaryImage(string $fixtureName, ?string $path = null): string
    {
        if ($path === null) {
            $path = tempnam(sys_get_temp_dir(), 'text-reader-image');
            if ($path === false) {
                $this->fail('Unable to create a temporary image file.');
            }
        }

        $this->assertTrue(
            copy(dirname(__DIR__) . '/sample/' . $fixtureName, $path),
            sprintf('Unable to copy fixture "%s" to temporary path.', $fixtureName)
        );

        return $path;
    }

    private function createTextReaderItem(string $mediaLink): Item
    {
        $item = new Item([
            'identifier' => self::ITEM_IDENTIFIER,
            'xml:lang' => self::LANGUAGE,
        ]);

        $interaction = new PortableCustomInteraction();
        $interaction->setTypeIdentifier('textReaderInteraction');
        $interaction->setProperties(
            [
                'pages' => json_encode(
                    [
                        [
                            'label' => 'Page 1',
                            'content' => [
                                sprintf('<img src="%s" alt="cat"/>', $mediaLink),
                            ],
                            'id' => 0,
                        ],
                    ]
                ),
            ]
        );

        $item->addInteraction(
            $interaction,
            sprintf('<div class="text-reader">%s</div>', $interaction->getPlaceholder())
        );
        $responseDeclaration = new ResponseDeclaration();
        $responseDeclaration->setIdentifier('RESPONSE_' . $interaction->getSerial());
        $item->addResponse($responseDeclaration);
        $interaction->setAttribute('responseIdentifier', $responseDeclaration->getIdentifier());

        return $item;
    }

    private function getTextReaderInteraction(Item $item): PortableCustomInteraction
    {
        $interactions = $item->getComposingElements(PortableCustomInteraction::class);
        $this->assertCount(1, $interactions);

        return current($interactions);
    }

    private function buildExpectedDataUrl(string $path): string
    {
        return sprintf(
            'data:image/png;base64,%s',
            base64_encode((string) file_get_contents($path))
        );
    }

    private function cleanupTemporaryImageArtifacts(): void
    {
        foreach (glob(sys_get_temp_dir() . '/text-reader-image*') ?: [] as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
    }

    private function removeDirectory(string $path): void
    {
        foreach (glob($path . '/*') ?: [] as $entry) {
            if (is_dir($entry)) {
                $this->removeDirectory($entry);
            } elseif (is_file($entry)) {
                @unlink($entry);
            }
        }

        @rmdir($path);
    }
}
