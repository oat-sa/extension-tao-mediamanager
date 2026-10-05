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

namespace oat\taoMediaManager\test\unit\model;

use core_kernel_classes_Resource;
use GuzzleHttp\Psr7\Utils;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\resources\relation\FindAllQuery;
use oat\tao\model\resources\relation\ResourceRelation;
use oat\tao\model\service\ApplicationService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\TextReaderInteractionQtiUpdater;
use oat\taoMediaManager\test\unit\model\mock\TextReaderReferencesExtractorMock;
use oat\taoQtiItem\model\qti\event\UpdatedItemEventDispatcher;
use oat\taoQtiItem\model\qti\interaction\PortableCustomInteraction;
use oat\taoQtiItem\model\qti\Item;
use oat\taoQtiItem\model\qti\ResponseDeclaration;
use oat\taoQtiItem\model\qti\Service as QtiService;
use oat\taoItems\model\media\ItemMediaResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use tao_helpers_Uri;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

// phpcs:disable PSR1.Files.SideEffects
require_once __DIR__ . '/mock/TextReaderReferencesExtractorMock.php';
// phpcs:enable PSR1.Files.SideEffects

class TextReaderInteractionQtiUpdaterTest extends TestCase
{
    private ServiceManager $originalServiceManager;
    private ServiceManager $serviceManagerMock;

    /** @var array<string, object> */
    private array $services = [];

    private const ITEM_URI = 'http://example.com/ontologies/tao.rdf#textReaderItem';
    private const ITEM_IDENTIFIER = 'item-1';
    private const LANGUAGE = 'en-US';
    private const MEDIA_ID = 'http://example.com/ontologies/tao.rdf#media';
    private const BRAZIL_PNG_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wnpa9sAAAAASUVORK5CYII=';
    private const ITALY_PNG_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAusBfZcfo3sAAAAASUVORK5CYII=';

    /** @var string[] */
    private array $temporaryImagePaths = [];

    protected function setUp(): void
    {
        $this->cleanupTemporaryImageArtifacts();

        if (!defined('PRODUCT_NAME')) {
            define('PRODUCT_NAME', 'TAO');
        }

        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('getPlatformVersion')
            ->willReturn('test-version');
        $this->services = [
            ApplicationService::SERVICE_ID => $applicationService,
        ];

        $this->originalServiceManager = ServiceManager::getServiceManager();
        $this->serviceManagerMock = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'has', 'overload', 'propagate'])
            ->getMock();
        $this->serviceManagerMock->method('get')
            ->willReturnCallback(fn (string $serviceId) => $this->services[$serviceId] ?? null);
        $this->serviceManagerMock->method('has')
            ->willReturnCallback(fn (string $serviceId): bool => array_key_exists($serviceId, $this->services));
        $this->serviceManagerMock->method('overload')
            ->willReturnCallback(function (string $serviceId, object $service): void {
                $this->services[$serviceId] = $service;
            });
        $this->serviceManagerMock->method('propagate')
            ->willReturnCallback(function (object $service): object {
                if ($service instanceof ServiceLocatorAwareInterface) {
                    $service->setServiceLocator($this->serviceManagerMock);
                }

                return $service;
            });

        ServiceManager::setServiceManager($this->serviceManagerMock);
    }

    protected function tearDown(): void
    {
        ServiceManager::setServiceManager($this->originalServiceManager);
        foreach ($this->temporaryImagePaths as $temporaryImagePath) {
            if (is_file($temporaryImagePath)) {
                unlink($temporaryImagePath);
            }
        }

        $this->cleanupTemporaryImageArtifacts();

        $this->services = [];
        $this->temporaryImagePaths = [];
    }

    public function testRefreshInteractionPropertiesReplacesTextReaderBase64AfterAssetReplacement(): void
    {
        $firstImagePath = $this->createTemporaryImage(self::BRAZIL_PNG_BASE64);
        $secondImagePath = $this->createTemporaryImage(self::ITALY_PNG_BASE64);
        $mediaLink = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(self::MEDIA_ID);
        $contentPropertyKey = 'content-' . $mediaLink;
        $item = $this->createTextReaderItem($mediaLink);
        $interaction = $this->getTextReaderInteraction($item);
        $itemWithExistingContent = $this->createTextReaderItem(
            $mediaLink,
            [$contentPropertyKey => 'data:image/png;base64,stale-image']
        );
        $interactionWithExistingContent = $this->getTextReaderInteraction($itemWithExistingContent);
        $textReaderReferencesExtractor = $this->createMock(TextReaderReferencesExtractorMock::class);
        $textReaderReferencesExtractor->method('extractFromInteraction')
            ->willReturnCallback(
                function (PortableCustomInteraction $currentInteraction) use (
                    $interaction,
                    $interactionWithExistingContent,
                    $mediaLink
                ): array {
                    if (
                        $currentInteraction === $interaction
                        || $currentInteraction === $interactionWithExistingContent
                    ) {
                        return [$mediaLink];
                    }

                    return [];
                }
            );

        $subject = new TextReaderInteractionQtiUpdater(
            $this->createMock(MediaRelationRepositoryInterface::class),
            $this->createMock(QtiService::class),
            $this->createMock(UpdatedItemEventDispatcher::class),
            $textReaderReferencesExtractor
        );

        $method = new \ReflectionMethod(TextReaderInteractionQtiUpdater::class, 'refreshInteractionProperties');
        $method->setAccessible(true);
        $extractMethod = new \ReflectionMethod(TextReaderInteractionQtiUpdater::class, 'extractMatchingImageSources');
        $extractMethod->setAccessible(true);
        $buildDataUrlMethod = new \ReflectionMethod(TextReaderInteractionQtiUpdater::class, 'buildDataUrl');
        $buildDataUrlMethod->setAccessible(true);
        $subject->setLogger(new NullLogger());

        $this->assertTrue(
            $method->invoke(
                $subject,
                $interaction,
                $this->createResolver($firstImagePath),
                self::MEDIA_ID
            )
        );
        $this->assertSame(
            $this->buildExpectedDataUrl($firstImagePath),
            $interaction->getProperties()[$contentPropertyKey]
        );

        $this->assertSame(
            [$mediaLink],
            $extractMethod->invoke(
                $subject,
                $interactionWithExistingContent,
                $this->createMatchingResolver($mediaLink),
                self::MEDIA_ID
            )
        );
        $this->assertSame(
            $this->buildExpectedDataUrl($secondImagePath),
            $buildDataUrlMethod->invoke($subject, $this->createDataUrlResolver($secondImagePath), $mediaLink)
        );

        $this->assertSame(
            $this->buildExpectedDataUrl($secondImagePath),
            $buildDataUrlMethod->invoke($subject, $this->createResolver($secondImagePath), $mediaLink)
        );
    }

    public function testRefreshByMediaIdUpdatesItemReferencedByResourceRelation(): void
    {
        $imagePath = $this->createTemporaryImage(self::BRAZIL_PNG_BASE64);
        $mediaLink = MediaSource::SCHEME_NAME . tao_helpers_Uri::encode(self::MEDIA_ID);
        $contentPropertyKey = 'content-' . $mediaLink;
        $item = $this->createTextReaderItem($mediaLink);
        $interaction = $this->getTextReaderInteraction($item);

        $mediaSource = $this->createMock(MediaSource::class);
        $mediaSource->expects($this->once())
            ->method('getFileInfo')
            ->with(tao_helpers_Uri::encode(self::MEDIA_ID))
            ->willReturn(['mime' => 'image/png']);
        $mediaSource->expects($this->once())
            ->method('getFileStream')
            ->with(tao_helpers_Uri::encode(self::MEDIA_ID))
            ->willReturn(Utils::streamFor((string) file_get_contents($imagePath)));

        $taoMediaService = new \oat\tao\model\media\MediaService(
            [
                \oat\tao\model\media\MediaService::OPTION_SOURCE => [
                    'mediamanager' => $mediaSource,
                ],
            ]
        );
        $taoMediaService->setLogger(new NullLogger());
        $this->serviceManagerMock->propagate($taoMediaService);
        $this->serviceManagerMock->overload(\oat\tao\model\media\MediaService::SERVICE_ID, $taoMediaService);
        $resolvedAsset = (new ItemMediaResolver(new core_kernel_classes_Resource(self::ITEM_URI), self::LANGUAGE))
            ->resolve($mediaLink);
        $this->assertInstanceOf(MediaSource::class, $resolvedAsset->getMediaSource());
        $this->assertSame(self::MEDIA_ID, tao_helpers_Uri::decode($resolvedAsset->getMediaIdentifier()));

        $repository = $this->createMock(MediaRelationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->with($this->callback(fn (FindAllQuery $query): bool => $query->getSourceId() === self::MEDIA_ID))
            ->willReturn(
                new MediaRelationCollection(
                    (new ResourceRelation('item', self::ITEM_URI))->withSourceId(self::MEDIA_ID)
                )
            );
        $resourceMatchesItemUri = fn (
            core_kernel_classes_Resource $resource
        ): bool => $resource->getUri() === self::ITEM_URI;

        $qtiService = $this->createMock(QtiService::class);
        $qtiService->expects($this->once())
            ->method('getDataItemByRdfItem')
            ->with($this->callback($resourceMatchesItemUri))
            ->willReturn($item);
        $qtiService->expects($this->once())
            ->method('saveDataItemToRdfItem')
            ->with(
                $item,
                $this->callback($resourceMatchesItemUri)
            );

        $eventDispatcher = $this->createMock(UpdatedItemEventDispatcher::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $item,
                $this->callback($resourceMatchesItemUri)
            );
        $textReaderReferencesExtractor = $this->createMock(TextReaderReferencesExtractorMock::class);
        $textReaderReferencesExtractor->expects($this->once())
            ->method('getTextReaderInteractions')
            ->with($item)
            ->willReturn([$interaction]);
        $textReaderReferencesExtractor->expects($this->once())
            ->method('extractFromInteraction')
            ->with($interaction)
            ->willReturn([$mediaLink]);

        $subject = new TextReaderInteractionQtiUpdater(
            $repository,
            $qtiService,
            $eventDispatcher,
            $textReaderReferencesExtractor
        );
        $subject->setLogger(new NullLogger());

        $this->assertSame(1, $subject->refreshByMediaId(self::MEDIA_ID));
        $this->assertSame(
            $this->buildExpectedDataUrl($imagePath),
            $this->getTextReaderInteraction($item)->getProperties()[$contentPropertyKey]
        );
    }

    private function createTextReaderItem(string $mediaLink, array $extraProperties = []): Item
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
        if ($extraProperties !== []) {
            $interaction->setProperties(array_merge($interaction->getProperties(), $extraProperties));
        }

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

    private function createTemporaryImage(string $base64Image): string
    {
        $path = tempnam(sys_get_temp_dir(), 'text-reader-image');
        if ($path === false) {
            $this->fail('Unable to create a temporary image file.');
        }

        $imageContents = base64_decode($base64Image, true);
        if ($imageContents === false) {
            $this->fail('Unable to decode temporary image contents.');
        }

        file_put_contents($path, $imageContents);
        $this->temporaryImagePaths[] = $path;

        return $path;
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

    private function createResolver(string $imagePath): ItemMediaResolver
    {
        $mediaSource = $this->createMock(MediaSource::class);
        $mediaSource->expects($this->once())
            ->method('getFileInfo')
            ->with(self::MEDIA_ID)
            ->willReturn(['mime' => 'image/png']);
        $mediaSource->expects($this->once())
            ->method('getFileStream')
            ->with(self::MEDIA_ID)
            ->willReturn(Utils::streamFor((string) file_get_contents($imagePath)));

        $asset = new MediaAsset($mediaSource, self::MEDIA_ID);
        $this->assertInstanceOf(MediaSource::class, $asset->getMediaSource());
        $this->assertSame(self::MEDIA_ID, tao_helpers_Uri::decode($asset->getMediaIdentifier()));

        $resolver = $this->getMockBuilder(ItemMediaResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolve'])
            ->getMock();
        $resolver->method('resolve')
            ->willReturn($asset);

        return $resolver;
    }

    private function createMatchingResolver(string $mediaLink): ItemMediaResolver
    {
        $asset = new MediaAsset($this->createMock(MediaSource::class), self::MEDIA_ID);

        $resolver = $this->getMockBuilder(ItemMediaResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolve'])
            ->getMock();
        $resolver->method('resolve')
            ->willReturn($asset);

        return $resolver;
    }

    private function createDataUrlResolver(string $imagePath): ItemMediaResolver
    {
        $mediaSource = $this->createMock(MediaSource::class);
        $mediaSource->expects($this->once())
            ->method('getFileInfo')
            ->with(self::MEDIA_ID)
            ->willReturn(['mime' => 'image/png']);
        $mediaSource->expects($this->once())
            ->method('getFileStream')
            ->with(self::MEDIA_ID)
            ->willReturn(Utils::streamFor((string) file_get_contents($imagePath)));

        $asset = new MediaAsset($mediaSource, self::MEDIA_ID);

        $resolver = $this->getMockBuilder(ItemMediaResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolve'])
            ->getMock();
        $resolver->method('resolve')
            ->willReturn($asset);

        return $resolver;
    }
}
