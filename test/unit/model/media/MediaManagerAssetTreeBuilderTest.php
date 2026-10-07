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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model\media;

use oat\generis\test\TestCase;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\mediaSource\DirectorySearchQuery;
use oat\taoItems\model\media\AssetIndexedSearchGatewayInterface;
use oat\taoItems\model\media\AssetSearchQuery;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\media\MediaManagerAssetTreeBuilder;

class MediaManagerAssetTreeBuilderTest extends TestCase
{
    /** @var MediaManagerAssetTreeBuilder */
    private $subject;

    private function setIndexedSearchGateway(
        MediaManagerAssetTreeBuilder $builder,
        ?AssetIndexedSearchGatewayInterface $gateway
    ): void {
        $property = new \ReflectionProperty(MediaManagerAssetTreeBuilder::class, 'indexedSearchGateway');
        $property->setAccessible(true);
        $property->setValue($builder, $gateway ?? false);
    }

    private function disableIndexedBrowse(MediaManagerAssetTreeBuilder $builder): void
    {
        $this->setIndexedSearchGateway($builder, null);
    }

    /** @return MediaSource&\PHPUnit\Framework\MockObject\MockObject */
    private function createMediaSourceMock(array $onlyMethods = ['getDirectories'])
    {
        $mediaSource = $this->getMockBuilder(MediaSource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_merge($onlyMethods, ['enableAccessControl']))
            ->getMock();
        $mediaSource->method('enableAccessControl')->willReturnSelf();

        return $mediaSource;
    }

    public function setUp(): void
    {
        $this->subject = new MediaManagerAssetTreeBuilder();
    }

    public function testBuildLazyFolderBrowseForMediaSourceUsesDepthOneAndOffset(): void
    {
        $this->disableIndexedBrowse($this->subject);

        $mediaSource = $this->createMediaSourceMock();

        $captured = null;
        $mediaSource->expects($this->once())
            ->method('getDirectories')
            ->with($this->callback(function (DirectorySearchQuery $query) use (&$captured): bool {
                $captured = $query;
                return true;
            }))
            ->willReturn([
                'path' => 'taomedia://mediamanager/',
                'label' => 'Media',
                'total' => 2,
                'children' => [
                    [
                        'parent' => 'https://test-tao.example/ontologies/tao.rdf#Folder',
                        'label' => 'Folder',
                    ],
                    [
                        'uri' => 'taomedia://mediamanager/root.png',
                        'name' => 'root.png',
                        'mime' => 'image/png',
                    ],
                ],
            ]);

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);

        $result = $this->subject->build(
            new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US', [], 1, 30)
        );

        $this->assertInstanceOf(AssetSearchQuery::class, $captured);
        $this->assertSame(1, $captured->getDepth());
        $this->assertSame(30, $captured->getChildrenOffset());
        $this->assertSame(15, $captured->getChildrenLimit());
        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['children']);
    }

    public function testMediaRootWithoutIndexedSearchListsOnlyDirectFiles(): void
    {
        $this->disableIndexedBrowse($this->subject);

        $mediaSource = $this->createMediaSourceMock();
        $mediaSource->method('getDirectories')->willReturn([
            'path' => 'taomedia://mediamanager/',
            'label' => 'Media',
            'total' => 2,
            'children' => [
                [
                    'path' => '/images',
                    'label' => 'images',
                    'children' => [
                        [
                            'uri' => 'taomedia://mediamanager/nested.png',
                            'name' => 'nested.png',
                            'mime' => 'image/png',
                        ],
                    ],
                ],
                [
                    'uri' => 'taomedia://mediamanager/root.png',
                    'name' => 'root.png',
                    'mime' => 'image/png',
                ],
            ],
        ]);

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn(MediaSource::SCHEME_NAME);

        $result = $this->subject->build(new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'));

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertCount(1, $files);
        $this->assertSame('root.png', $files[0]['name']);
    }

    public function testBuildLazyFolderBrowseForMediaSourceDoesNotFlattenNestedFiles(): void
    {
        $this->disableIndexedBrowse($this->subject);

        $mediaSource = $this->createMediaSourceMock();

        $mediaSource->method('getDirectories')->willReturn([
            'path' => 'taomedia://mediamanager/',
            'label' => 'Root',
            'total' => 1,
            'children' => [
                [
                    'path' => '/images',
                    'label' => 'images',
                    'children' => [
                        [
                            'uri' => 'taomedia://mediamanager/nested.png',
                            'name' => 'nested.png',
                            'mime' => 'image/png',
                        ],
                    ],
                ],
                [
                    'uri' => 'taomedia://mediamanager/root.png',
                    'name' => 'root.png',
                    'mime' => 'image/png',
                ],
            ],
        ]);

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);

        $result = $this->subject->build(
            new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US')
        );

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertCount(1, $files);
        $this->assertSame('root.png', $files[0]['name']);
    }

    public function testIndexedBrowseUsesElasticsearchListWithoutOntologyMerge(): void
    {
        $gateway = $this->createMock(AssetIndexedSearchGatewayInterface::class);
        $gateway->method('isAvailable')->willReturn(true);
        $gateway->method('search')->willReturn([
            'items' => [
                [
                    'uri' => 'taomedia://mediamanager/existing.png',
                    'name' => 'existing.png',
                    'mime' => 'image/png',
                ],
            ],
            'total' => 1,
            'page' => 1,
            'pageSize' => 15,
        ]);
        $this->setIndexedSearchGateway($this->subject, $gateway);

        $mediaSource = $this->createMediaSourceMock();
        $mediaSource->method('getDirectories')->willReturnCallback(
            static function (DirectorySearchQuery $query): array {
                if ($query->getChildrenLimit() === MediaSource::CHILDREN_LIMIT_DIRECTORIES_ONLY) {
                    return [
                        'path' => 'taomedia://mediamanager/',
                        'label' => 'Media',
                        'children' => [],
                    ];
                }

                return [
                    'path' => 'taomedia://mediamanager/',
                    'label' => 'Media',
                    'total' => 2,
                    'children' => [
                        [
                            'uri' => 'taomedia://mediamanager/existing.png',
                            'name' => 'existing.png',
                            'mime' => 'image/png',
                        ],
                        [
                            'uri' => 'taomedia://mediamanager/fresh-upload.png',
                            'name' => 'fresh-upload.png',
                            'mime' => 'image/png',
                        ],
                    ],
                ];
            }
        );

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn(MediaSource::SCHEME_NAME);

        $result = $this->subject->build(new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'));

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $files);
        $this->assertSame('existing.png', $files[0]['name']);
    }

    public function testIndexedRootBrowseUsesElasticsearchPageOnly(): void
    {
        $gateway = $this->createMock(AssetIndexedSearchGatewayInterface::class);
        $gateway->method('isAvailable')->willReturn(true);
        $gateway->method('search')->willReturn([
            'items' => [
                [
                    'uri' => 'taomedia://mediamanager/nested.png',
                    'name' => 'nested.png',
                    'mime' => 'image/png',
                    'location' => 'Media / images',
                ],
            ],
            'total' => 50,
            'page' => 1,
            'pageSize' => 15,
        ]);
        $this->setIndexedSearchGateway($this->subject, $gateway);

        $mediaSource = $this->createMediaSourceMock();
        $mediaSource->method('getDirectories')->willReturnCallback(
            static function (DirectorySearchQuery $query): array {
                if ($query->getChildrenLimit() === MediaSource::CHILDREN_LIMIT_DIRECTORIES_ONLY) {
                    return [
                        'path' => 'taomedia://mediamanager/',
                        'label' => 'Media',
                        'children' => [
                            ['path' => '/images', 'label' => 'images'],
                        ],
                    ];
                }

                return [
                    'path' => 'taomedia://mediamanager/',
                    'label' => 'Media',
                    'total' => 1,
                    'children' => [
                        [
                            'uri' => 'taomedia://mediamanager/root-only.png',
                            'name' => 'root-only.png',
                            'mime' => 'image/png',
                        ],
                    ],
                ];
            }
        );

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn(MediaSource::SCHEME_NAME);

        $result = $this->subject->build(new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'));

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertSame(50, $result['total']);
        $this->assertCount(1, $files);
        $this->assertSame('nested.png', $files[0]['name']);
    }

    public function testBuildAssetListWithoutIndexedSearchListsOnlyDirectFilesAtRoot(): void
    {
        $this->disableIndexedBrowse($this->subject);

        $mediaSource = $this->createMediaSourceMock();
        $captured = null;
        $mediaSource->expects($this->once())
            ->method('getDirectories')
            ->with($this->callback(function (DirectorySearchQuery $query) use (&$captured): bool {
                $captured = $query;

                return true;
            }))
            ->willReturn([
            'path' => 'taomedia://mediamanager/',
            'label' => 'Media',
            'total' => 1,
            'children' => [
                [
                    'path' => '/images',
                    'label' => 'images',
                ],
                [
                    'uri' => 'taomedia://mediamanager/root.png',
                    'name' => 'root.png',
                    'mime' => 'image/png',
                ],
            ],
        ]);

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn(MediaSource::SCHEME_NAME);

        $result = $this->subject->buildAssetList(
            (new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'))
                ->setPage(1)
                ->setPageSize(15)
        );

        $this->assertInstanceOf(AssetSearchQuery::class, $captured);
        $this->assertSame(0, $captured->getChildrenOffset());
        $this->assertSame(15, $captured->getChildrenLimit());
        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('root.png', $result['items'][0]['name']);
        $this->assertFalse($result['totalIsApproximate']);
    }

    public function testBuildAssetListOntologyLastPageKeepsSourceTotal(): void
    {
        $this->disableIndexedBrowse($this->subject);

        $mediaSource = $this->createMediaSourceMock();
        $mediaSource->method('getDirectories')->willReturnCallback(
            static function (DirectorySearchQuery $query): array {
                $offset = $query->getChildrenOffset();
                $limit = $query->getChildrenLimit();
                $all = [];
                for ($i = 1; $i <= 12; $i++) {
                    $all[] = [
                        'uri' => 'taomedia://mediamanager/file-' . $i . '.png',
                        'name' => sprintf('file-%02d.png', $i),
                        'mime' => 'image/png',
                    ];
                }
                $slice = array_slice($all, $offset, $limit > 0 ? $limit : null);

                return [
                    'path' => 'taomedia://mediamanager/folder',
                    'label' => 'Folder',
                    'total' => 12,
                    'children' => $slice,
                ];
            }
        );

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn(MediaSource::SCHEME_NAME);

        $pageTwo = $this->subject->buildAssetList(
            (new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'))
                ->setPage(2)
                ->setPageSize(11)
        );

        $this->assertSame(12, $pageTwo['total']);
        $this->assertCount(1, $pageTwo['items']);
        $this->assertSame('file-12.png', $pageTwo['items'][0]['name']);
        $this->assertFalse($pageTwo['truncated']);
    }

    public function testBuildTreeReturnsDirectoryStubsWithoutFileRows(): void
    {
        $this->disableIndexedBrowse($this->subject);

        $mediaSource = $this->createMediaSourceMock();
        $captured = null;
        $mediaSource->expects($this->once())
            ->method('getDirectories')
            ->with($this->callback(function (DirectorySearchQuery $query) use (&$captured): bool {
                $captured = $query;

                return true;
            }))
            ->willReturn([
                'path' => 'taomedia://mediamanager/',
                'label' => 'Media',
                'children' => [
                    ['path' => '/images', 'label' => 'images'],
                    [
                        'uri' => 'taomedia://mediamanager/root.png',
                        'name' => 'root.png',
                        'mime' => 'image/png',
                    ],
                ],
            ]);

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);

        $result = $this->subject->buildTree(new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'));

        $this->assertInstanceOf(AssetSearchQuery::class, $captured);
        $this->assertSame(1, $captured->getDepth());
        $this->assertSame(MediaSource::CHILDREN_LIMIT_DIRECTORIES_ONLY, $captured->getChildrenLimit());
        $this->assertCount(1, $result['children']);
        $this->assertSame('images', $result['children'][0]['label']);
        $this->assertArrayNotHasKey('total', $result);
    }

    public function testBuildAssetListForwardsPageToIndexedGateway(): void
    {
        $gateway = $this->createMock(AssetIndexedSearchGatewayInterface::class);
        $gateway->method('isAvailable')->willReturn(true);
        $capturedIndexQuery = null;
        $gateway->method('search')->willReturnCallback(
            static function (AssetSearchQuery $query) use (&$capturedIndexQuery): array {
                $capturedIndexQuery = $query;

                return [
                    'items' => [
                        [
                            'uri' => 'taomedia://mediamanager/page-two.png',
                            'name' => 'page-two.png',
                            'mime' => 'image/png',
                        ],
                    ],
                    'total' => 200,
                    'page' => 2,
                    'pageSize' => 15,
                ];
            }
        );
        $this->setIndexedSearchGateway($this->subject, $gateway);

        $mediaSource = $this->createMediaSourceMock();
        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn(MediaSource::SCHEME_NAME);

        $result = $this->subject->buildAssetList(
            (new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'))
                ->setPage(2)
                ->setPageSize(15)
        );

        $this->assertInstanceOf(AssetSearchQuery::class, $capturedIndexQuery);
        $this->assertSame(2, $capturedIndexQuery->getPage());
        $this->assertSame(15, $capturedIndexQuery->getPageSize());
        $this->assertSame('page-two.png', $result['items'][0]['name']);
    }

    public function testIndexedSubfolderBrowseSecondPageKeepsElasticsearchRows(): void
    {
        $gateway = $this->createMock(AssetIndexedSearchGatewayInterface::class);
        $gateway->method('isAvailable')->willReturn(true);
        $gateway->method('search')->willReturn([
            'items' => [
                [
                    'uri' => 'taomedia://mediamanager/nested-page-two.png',
                    'name' => 'nested-page-two.png',
                    'mime' => 'image/png',
                ],
            ],
            'total' => 200,
            'page' => 2,
            'pageSize' => 15,
            'totalIsApproximate' => true,
        ]);
        $this->setIndexedSearchGateway($this->subject, $gateway);

        $folderClassUri = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetsFolder';
        $folderPath = MediaSource::SCHEME_NAME . \tao_helpers_Uri::encode($folderClassUri);

        $mediaSource = $this->createMediaSourceMock();
        $mediaSource->method('getDirectories')->willReturnCallback(
            static function (DirectorySearchQuery $query) use ($folderPath): array {
                if ($query->getChildrenLimit() === MediaSource::CHILDREN_LIMIT_DIRECTORIES_ONLY) {
                    return [
                        'path' => $folderPath,
                        'label' => 'Assets',
                        'children' => [
                            ['path' => '/batch-001', 'label' => 'batch-001'],
                        ],
                    ];
                }

                return [
                    'path' => $folderPath,
                    'label' => 'Assets',
                    'total' => 3,
                    'children' => [],
                ];
            }
        );

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);
        $mediaAsset->method('getMediaIdentifier')->willReturn($folderPath);

        $result = $this->subject->build(
            new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US', [], 1, 15)
        );

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertSame(200, $result['total']);
        $this->assertCount(1, $files);
        $this->assertSame('nested-page-two.png', $files[0]['name']);
    }
}
