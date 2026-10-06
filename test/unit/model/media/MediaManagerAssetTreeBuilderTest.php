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

    public function testIndexedBrowseMergesOntologyFilesMissingFromElasticsearch(): void
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

        $result = $this->subject->build(new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'));

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $files);
        $this->assertSame(
            ['existing.png', 'fresh-upload.png'],
            array_map(static function (array $file): string {
                return (string)$file['name'];
            }, $files)
        );
    }

    public function testIndexedBrowseDropsElasticsearchRowsRemovedFromOntology(): void
    {
        $gateway = $this->createMock(AssetIndexedSearchGatewayInterface::class);
        $gateway->method('isAvailable')->willReturn(true);
        $gateway->method('search')->willReturn([
            'items' => [
                [
                    'uri' => 'taomedia://mediamanager/deleted.png',
                    'name' => 'deleted.png',
                    'mime' => 'image/png',
                ],
                [
                    'uri' => 'taomedia://mediamanager/kept.png',
                    'name' => 'kept.png',
                    'mime' => 'image/png',
                ],
            ],
            'total' => 2,
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
                    'total' => 1,
                    'children' => [
                        [
                            'uri' => 'taomedia://mediamanager/kept.png',
                            'name' => 'kept.png',
                            'mime' => 'image/png',
                        ],
                    ],
                ];
            }
        );

        $mediaAsset = $this->createMock(MediaAsset::class);
        $mediaAsset->method('getMediaSource')->willReturn($mediaSource);

        $result = $this->subject->build(new AssetSearchQuery($mediaAsset, 'item-uri', 'en-US'));

        $files = array_values(array_filter(
            $result['children'],
            static function (array $child): bool {
                return isset($child['uri']);
            }
        ));

        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $files);
        $this->assertSame('kept.png', $files[0]['name']);
    }
}
