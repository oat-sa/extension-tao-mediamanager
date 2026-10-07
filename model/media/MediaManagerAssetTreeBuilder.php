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

namespace oat\taoMediaManager\model\media;

use oat\oatbox\service\ServiceManager;
use oat\tao\model\accessControl\AccessControlEnablerInterface;
use oat\tao\model\media\mediaSource\DirectorySearchQuery;
use oat\taoItems\model\media\AssetBrowseListBuilderInterface;
use oat\taoItems\model\media\AssetIndexedSearchGatewayInterface;
use oat\taoItems\model\media\AssetSearchQuery;
use oat\taoItems\model\media\AssetTreeBuilder;
use oat\taoItems\model\media\LocalItemSource;
use oat\taoItems\model\media\NoOpAssetIndexedSearchGateway;
use oat\taoMediaManager\model\MediaSource;

/**
 * Resource Manager browse for {@see MediaSource}.
 *
 * Legacy {@see build()} returns combined tree + list. Prefer {@see buildTree()} and
 * {@see buildAssetList()} via {@code part=tree|list} on {@code ItemContent::files}.
 */
class MediaManagerAssetTreeBuilder extends AssetTreeBuilder implements AssetBrowseListBuilderInterface
{
    /** One level per browse request; deeper tree levels load on folder click. */
    private const BROWSE_LAZY_FOLDER_DEPTH = 1;

    /** @var AssetIndexedSearchGatewayInterface|false|null */
    private $indexedSearchGateway;

    public function build(DirectorySearchQuery $search): array
    {
        if ($this->canUseIndexedBrowse()) {
            $indexedBrowse = $this->tryBuildViaIndexedSearch($search);
            if ($indexedBrowse !== null) {
                return $indexedBrowse;
            }
        }

        $mediaSource = $search->getAsset()->getMediaSource();
        if ($mediaSource instanceof MediaSource) {
            $pageSize = $this->getPaginationLimit();
            $offset = max(0, min($search->getChildrenOffset(), self::MAX_CHILDREN_OFFSET));

            return $this->buildLazyFolderBrowse($search, $pageSize, $offset);
        }

        return parent::build($search);
    }

    public function buildTree(DirectorySearchQuery $search): array
    {
        $mediaSource = $search->getAsset()->getMediaSource();
        if (!$mediaSource instanceof MediaSource) {
            return parent::buildTree($search);
        }

        return $this->buildFolderStubsOnly($search);
    }

    public function buildAssetList(DirectorySearchQuery $search): array
    {
        $mediaSource = $search->getAsset()->getMediaSource();
        if (!$mediaSource instanceof MediaSource) {
            return $this->buildAssetListViaSearchBuilderFallback($search);
        }

        if ($this->canUseIndexedBrowse()) {
            $indexed = $this->tryBuildAssetListViaIndex($search);
            if ($indexed !== null) {
                return $indexed;
            }
        }

        return $this->buildAssetListViaOntology($search);
    }

    private function canUseIndexedBrowse(): bool
    {
        $gateway = $this->getIndexedSearchGateway();
        if ($gateway === null) {
            return false;
        }

        try {
            return $gateway->isAvailable();
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFolderStubsOnly(DirectorySearchQuery $search): array
    {
        $mediaSource = $search->getAsset()->getMediaSource();
        if ($mediaSource instanceof AccessControlEnablerInterface) {
            $mediaSource->enableAccessControl();
        }

        $fetchQuery = (new AssetSearchQuery(
            $search->getAsset(),
            $search->getItemUri(),
            $search->getItemLang(),
            $search->getFilter(),
            self::BROWSE_LAZY_FOLDER_DEPTH,
            0,
            MediaSource::CHILDREN_LIMIT_DIRECTORIES_ONLY
        ))
            ->setSortBy($this->resolveSortBy($search))
            ->setSortDir($this->resolveSortDir($search));

        $data = $mediaSource->getDirectories($fetchQuery);

        return $this->stripFileChildrenFromBrowseNode($data, $search);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryBuildAssetListViaIndex(DirectorySearchQuery $search): ?array
    {
        $mediaSource = $search->getAsset()->getMediaSource();
        if ($mediaSource instanceof LocalItemSource || !$mediaSource instanceof MediaSource) {
            return null;
        }

        $gateway = $this->getIndexedSearchGateway();
        if ($gateway === null) {
            return null;
        }

        $pageSize = max(1, $search->getPageSize());
        $page = max(1, $search->getPage());

        $indexQuery = (new AssetSearchQuery(
            $search->getAsset(),
            $search->getItemUri(),
            $search->getItemLang(),
            $search->getFilter(),
            1,
            0,
            0
        ))
            ->setSortBy($this->resolveSortBy($search))
            ->setSortDir($this->resolveSortDir($search))
            ->setPage($page)
            ->setPageSize($pageSize);

        try {
            $searchResult = $gateway->search($indexQuery);
        } catch (\Throwable $exception) {
            return null;
        }

        $items = [];
        foreach ($searchResult['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = $this->normalizeFile($item, '');
        }

        return [
            'items' => array_values($items),
            'total' => (int)($searchResult['total'] ?? 0),
            'page' => (int)($searchResult['page'] ?? $page),
            'pageSize' => (int)($searchResult['pageSize'] ?? $pageSize),
            'totalIsApproximate' => !empty($searchResult['totalIsApproximate']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAssetListViaOntology(DirectorySearchQuery $search): array
    {
        $pageSize = max(1, $search->getPageSize() ?: $this->getPaginationLimit());
        $page = max(1, $search->getPage());
        $offset = ($page - 1) * $pageSize;

        $folderBrowse = $this->buildLazyFolderBrowse($search, $pageSize, $offset);
        $scopeLabel = (string)($folderBrowse['locationPath'] ?? $folderBrowse['label'] ?? $folderBrowse['path'] ?? '');

        $items = [];
        foreach ($folderBrowse['children'] ?? [] as $child) {
            if (is_array($child) && isset($child['uri'])) {
                $items[] = $child;
            }
        }

        return [
            'items' => array_values($items),
            'total' => (int)($folderBrowse['total'] ?? count($items)),
            'page' => $page,
            'pageSize' => $pageSize,
            'totalIsApproximate' => false,
            'truncated' => !empty($folderBrowse['truncated']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAssetListViaSearchBuilderFallback(DirectorySearchQuery $search): array
    {
        return [
            'items' => [],
            'total' => 0,
            'page' => max(1, $search->getPage()),
            'pageSize' => max(1, $search->getPageSize() ?: $this->getPaginationLimit()),
            'totalIsApproximate' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLazyFolderBrowse(DirectorySearchQuery $search, int $pageSize, int $offset): array
    {
        $mediaSource = $search->getAsset()->getMediaSource();
        $fetchQuery = (new AssetSearchQuery(
            $search->getAsset(),
            $search->getItemUri(),
            $search->getItemLang(),
            $search->getFilter(),
            self::BROWSE_LAZY_FOLDER_DEPTH,
            $offset,
            $pageSize
        ))
            ->setSortBy($this->resolveSortBy($search))
            ->setSortDir($this->resolveSortDir($search));

        $data = $mediaSource->getDirectories($fetchQuery);
        $sourceReportedTotal = array_key_exists('total', $data) ? (int)$data['total'] : null;
        $scopeLabel = (string)($data['locationPath'] ?? $data['label'] ?? $data['path'] ?? '');

        $directories = [];
        $files = [];
        foreach ($data['children'] ?? [] as $child) {
            if (!is_array($child)) {
                continue;
            }
            if ($this->isFileChild($child)) {
                $files[] = $this->normalizeFile($child, $scopeLabel);
                continue;
            }
            if ($this->isDirectoryChild($child)) {
                $directories[] = $this->toDirectoryStub($child, $search);
            }
        }

        $files = $this->sortFiles($files, $this->resolveSortBy($search), $this->resolveSortDir($search));
        $fileCount = count($files);
        $total = $sourceReportedTotal !== null ? $sourceReportedTotal : $fileCount;

        $data['total'] = $total;
        $data['truncated'] = $total > $offset + $fileCount;
        $data['childrenLimit'] = $pageSize;
        $data['children'] = array_merge($directories, $files);

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryBuildViaIndexedSearch(DirectorySearchQuery $search): ?array
    {
        $tree = $this->buildTree($search);
        $list = $this->tryBuildAssetListViaIndex($search);
        if ($list === null) {
            return null;
        }

        $pageSize = (int)($list['pageSize'] ?? $this->getPaginationLimit());
        $page = (int)($list['page'] ?? 1);
        $pageOffset = ($page - 1) * $pageSize;

        $tree['total'] = (int)($list['total'] ?? 0);
        $tree['truncated'] = !empty($list['totalIsApproximate']);
        $tree['childrenLimit'] = $pageSize;
        $tree['children'] = array_merge($tree['children'] ?? [], $list['items'] ?? []);

        return $tree;
    }

    private function getIndexedSearchGateway(): ?AssetIndexedSearchGatewayInterface
    {
        if ($this->indexedSearchGateway instanceof AssetIndexedSearchGatewayInterface) {
            return $this->indexedSearchGateway;
        }

        if ($this->indexedSearchGateway === false) {
            return null;
        }

        $container = ServiceManager::getServiceManager()->getContainer();
        if (!$container->has(AssetIndexedSearchGatewayInterface::class)) {
            $this->indexedSearchGateway = false;

            return null;
        }

        $gateway = $container->get(AssetIndexedSearchGatewayInterface::class);
        if (
            !$gateway instanceof AssetIndexedSearchGatewayInterface
            || $gateway instanceof NoOpAssetIndexedSearchGateway
        ) {
            $this->indexedSearchGateway = false;

            return null;
        }

        $this->indexedSearchGateway = $gateway;

        return $gateway;
    }
}
