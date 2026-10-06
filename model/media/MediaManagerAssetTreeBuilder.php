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
use oat\taoItems\model\media\AssetIndexedSearchGatewayInterface;
use oat\taoItems\model\media\AssetSearchQuery;
use oat\taoItems\model\media\AssetTreeBuilder;
use oat\taoItems\model\media\LocalItemSource;
use oat\taoItems\model\media\NoOpAssetIndexedSearchGateway;
use oat\taoMediaManager\model\MediaSource;

/**
 * Resource Manager browse for {@see MediaSource}.
 *
 * With Elasticsearch: file rows come from the index (full subtree under the open folder).
 * Without Elasticsearch: depth-1 lazy browse only — at media root that means direct files
 * and subfolder stubs, not nested files from lower levels.
 */
class MediaManagerAssetTreeBuilder extends AssetTreeBuilder
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
        $mediaSource = $search->getAsset()->getMediaSource();
        if ($mediaSource instanceof LocalItemSource || !$mediaSource instanceof MediaSource) {
            return null;
        }

        $gateway = $this->getIndexedSearchGateway();
        if ($gateway === null) {
            return null;
        }

        $pageSize = $this->getPaginationLimit();
        $offset = max(0, min($search->getChildrenOffset(), self::MAX_CHILDREN_OFFSET));

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
            ->setPageSize($pageSize);

        $effectivePageSize = $indexQuery->getPageSize();
        $page = $effectivePageSize > 0
            ? (int) floor($offset / $effectivePageSize) + 1
            : AssetSearchQuery::DEFAULT_PAGE;
        $indexQuery->setPage($page);

        try {
            $searchResult = $gateway->search($indexQuery);
        } catch (\Throwable $exception) {
            return null;
        }

        if ($mediaSource instanceof AccessControlEnablerInterface) {
            $mediaSource->enableAccessControl();
        }

        $directoryQuery = (new AssetSearchQuery(
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

        try {
            $data = $mediaSource->getDirectories($directoryQuery);
        } catch (\Throwable $exception) {
            return null;
        }
        $scopeLabel = (string)($data['locationPath'] ?? $data['label'] ?? $data['path'] ?? '');
        $directories = [];
        foreach ($data['children'] ?? [] as $child) {
            if (!is_array($child) || !$this->isDirectoryChild($child)) {
                continue;
            }
            $directories[] = $this->toDirectoryStub($child, $search);
        }

        $sortBy = $this->resolveSortBy($search);
        $sortDir = $this->resolveSortDir($search);

        $files = [];
        foreach ($searchResult['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $files[] = $this->normalizeFile($item, $scopeLabel);
        }

        $files = $this->mergeDirectOntologyUploadsIntoIndexedBrowse(
            $search,
            $mediaSource,
            $offset,
            $effectivePageSize,
            $scopeLabel,
            $files,
            $sortBy,
            $sortDir
        );
        $total = (int)($searchResult['total'] ?? count($files));
        $pageOffset = $effectivePageSize > 0 ? ($page - 1) * $effectivePageSize : 0;
        $data['total'] = $total;
        $data['truncated'] = !empty($searchResult['totalIsApproximate'])
            || $total > $pageOffset + count($files);
        $data['childrenLimit'] = $effectivePageSize;
        $data['children'] = array_merge($directories, $files);

        return $data;
    }

    /**
     * Indexed browse paginates via Elasticsearch (subtree scope); merge direct folder uploads only.
     *
     * @param list<array<string, mixed>> $indexedFiles
     * @return list<array<string, mixed>>
     */
    private function mergeDirectOntologyUploadsIntoIndexedBrowse(
        DirectorySearchQuery $search,
        MediaSource $mediaSource,
        int $offset,
        int $pageSize,
        string $scopeLabel,
        array $indexedFiles,
        ?string $sortBy,
        ?string $sortDir
    ): array {
        if ($pageSize <= 0) {
            return $indexedFiles;
        }

        $esPageFiles = $indexedFiles;
        $esPageUris = [];
        foreach ($esPageFiles as $file) {
            $uri = (string)($file['uri'] ?? '');
            if ($uri !== '') {
                $esPageUris[$uri] = true;
            }
        }

        $fetchQuery = (new AssetSearchQuery(
            $search->getAsset(),
            $search->getItemUri(),
            $search->getItemLang(),
            $search->getFilter(),
            self::BROWSE_LAZY_FOLDER_DEPTH,
            $offset,
            $pageSize
        ))
            ->setSortBy($sortBy)
            ->setSortDir($sortDir);

        try {
            $ontologyData = $mediaSource->getDirectories($fetchQuery);
        } catch (\Throwable $exception) {
            return $indexedFiles;
        }

        foreach ($ontologyData['children'] ?? [] as $child) {
            if (!is_array($child) || !$this->isFileChild($child)) {
                continue;
            }
            $uri = (string)($child['uri'] ?? '');
            if ($uri === '' || isset($esPageUris[$uri])) {
                continue;
            }
            $indexedFiles[] = $this->normalizeFile($child, $scopeLabel);
            $esPageUris[$uri] = true;
        }

        $indexedFiles = $this->sortFiles($indexedFiles, $sortBy, $sortDir);
        if (count($indexedFiles) <= $pageSize) {
            return $indexedFiles;
        }

        $pageByUri = [];
        foreach ($esPageFiles as $file) {
            $uri = (string)($file['uri'] ?? '');
            if ($uri === '') {
                continue;
            }
            $pageByUri[$uri] = $file;
        }
        foreach ($indexedFiles as $file) {
            if (count($pageByUri) >= $pageSize) {
                break;
            }
            $uri = (string)($file['uri'] ?? '');
            if ($uri === '' || isset($pageByUri[$uri])) {
                continue;
            }
            $pageByUri[$uri] = $file;
        }

        return array_values($pageByUri);
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
