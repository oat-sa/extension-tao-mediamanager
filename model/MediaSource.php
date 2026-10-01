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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoMediaManager\model;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\Configurable;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\accessControl\AccessControlEnablerInterface;
use oat\tao\model\media\MediaManagement;
use oat\tao\model\media\mediaSource\DirectorySearchQuery;
use oat\tao\model\media\ProcessedFileStreamAware;
use oat\tao\model\TaoOntology;
use oat\taoMediaManager\model\export\service\MediaResourcePreparerInterface;
use oat\taoMediaManager\model\mapper\MediaSourcePermissionsMapper;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use Psr\Http\Message\StreamInterface;
use core_kernel_classes_Literal;
use core_kernel_classes_Resource;
use tao_helpers_Uri;
use tao_models_classes_FileNotFoundException;
use GuzzleHttp\Psr7\Utils;

class MediaSource extends Configurable implements
    MediaManagement,
    ProcessedFileStreamAware,
    AccessControlEnablerInterface
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    public const SCHEME_NAME = 'taomedia://mediamanager/';

    /** @var MediaService */
    protected $mediaService;

    /** @var FileManagement */
    protected $fileManagementService;

    /** @var MediaSourcePermissionsMapper */
    private $permissionsMapper;

    /** @var string[] */
    private $tmpFiles = [];

    public function enableAccessControl(): AccessControlEnablerInterface
    {
        $this->getPermissionsMapper()->enableAccessControl();

        return $this;
    }

    /**
     * Returns the language URI to be used
     * @return string
     */
    protected function getLanguage()
    {
        return $this->hasOption('lang')
            ? $this->getOption('lang')
            : '';
    }

    public function getRootClass()
    {
        return $this->getClass($this->getRootClassUri());
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaManagement::add
     */
    public function add($source, $fileName, $parent, $mimetype = null)
    {
        if (!file_exists($source)) {
            throw new tao_models_classes_FileNotFoundException($source);
        }

        $clazz = $this->getOrCreatePath($parent);

        $service = $this->getMediaService();
        $instanceUri = $service->createMediaInstance($source, $clazz->getUri(), $this->getLang(), $fileName, $mimetype);

        return $this->getFileInfo($instanceUri);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaManagement::delete
     */
    public function delete($link)
    {
        return $this->getMediaService()->deleteResource($this->getResource(tao_helpers_Uri::decode($link)));
    }

    public function getDirectories(DirectorySearchQuery $params): array
    {
        return $this->searchDirectories(
            $params->getParentLink(),
            $params->getFilter(),
            $params->getDepth(),
            $params->getChildrenLimit(),
            $params->getChildrenOffset()
        );
    }

    /**
     * @inheritDoc
     */
    public function getDirectory($parentLink = '', $acceptableMime = [], $depth = 1)
    {
        return $this->searchDirectories($parentLink, $acceptableMime, $depth, 0, 0);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaBrowser::getFileInfo
     */
    public function getFileInfo($link)
    {
        // get the media link from the resource
        $resource = $this->getResource(tao_helpers_Uri::decode($this->removeSchemaFromUriOrLink($link)));
        $properties = [
            $this->getProperty(TaoMediaOntology::PROPERTY_LINK),
            $this->getProperty(TaoMediaOntology::PROPERTY_MIME_TYPE),
            $this->getProperty(TaoMediaOntology::PROPERTY_ALT_TEXT)
        ];

        $propertiesValues = $resource->getPropertiesValues($properties);

        $fileLink = $propertiesValues[TaoMediaOntology::PROPERTY_LINK][0] ?? null;
        $mime = $propertiesValues[TaoMediaOntology::PROPERTY_MIME_TYPE][0] ?? null;
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        $fileLink = $this->getFileSourceUnserializer()->unserialize($fileLink);

        if (!isset($mime, $fileLink)) {
            throw new tao_models_classes_FileNotFoundException($link);
        }

        // add the alt text to file array
        $altArray = $propertiesValues[TaoMediaOntology::PROPERTY_ALT_TEXT] ?? null;
        $alt = $resource->getLabel();
        if (count($altArray) > 0) {
            $alt = (string)$altArray[0];
        }

        return $this->getPermissionsMapper()->map(
            [
                'name' => $resource->getLabel(),
                'uri' => self::SCHEME_NAME . tao_helpers_Uri::encode($link),
                'mime' => (string)$mime,
                'size' => $this->getFileManagement()->getFileSize($fileLink),
                'alt' => $alt,
                'link' => $fileLink,
                'updatedAt' => $this->formatResourceUpdatedAt($resource),
            ],
            $resource->getUri()
        );
    }

    /**
     * ISO-8601 UTC timestamp for Resource Manager "Last modified" column.
     */
    private function formatResourceUpdatedAt(core_kernel_classes_Resource $resource): ?string
    {
        $raw = $resource->getOnePropertyValue($this->getProperty(TaoOntology::PROPERTY_UPDATED_AT));
        $updatedAt = null;
        if ($raw instanceof core_kernel_classes_Literal) {
            $updatedAt = $raw->literal;
        } elseif ($raw !== null && $raw !== '') {
            $updatedAt = $raw;
        }

        if (is_string($updatedAt) && preg_match('/^\d+(\.\d+)?$/', $updatedAt) === 1) {
            $timestamp = (int) floor((float) $updatedAt);
        } elseif (is_int($updatedAt) || (is_string($updatedAt) && ctype_digit($updatedAt))) {
            $timestamp = (int) $updatedAt;
        } else {
            return $this->formatFileUpdatedAtForResource($resource);
        }

        if ($timestamp <= 0) {
            return $this->formatFileUpdatedAtForResource($resource);
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    private function formatFileUpdatedAtForResource(core_kernel_classes_Resource $resource): ?string
    {
        try {
            $fileLinkRaw = $resource->getOnePropertyValue(
                $this->getProperty(TaoMediaOntology::PROPERTY_LINK)
            );
            if ($fileLinkRaw === null || $fileLinkRaw === '') {
                return null;
            }

            if ($fileLinkRaw instanceof core_kernel_classes_Literal) {
                $fileLink = (string) $fileLinkRaw->literal;
            } elseif ($fileLinkRaw instanceof core_kernel_classes_Resource) {
                $fileLink = $fileLinkRaw->getUri();
            } else {
                $fileLink = (string) $fileLinkRaw;
            }
            $fileLink = $this->getFileSourceUnserializer()->unserialize($fileLink);
            if ($fileLink === '') {
                return null;
            }

            $fileManagement = $this->getFileManagement();
            if ($fileManagement instanceof \oat\taoMediaManager\model\fileManagement\FlySystemManagement) {
                $filesystem = $this->getServiceLocator()
                    ->get(\oat\oatbox\filesystem\FileSystemService::SERVICE_ID)
                    ->getFileSystem($fileManagement->getOption(
                        \oat\taoMediaManager\model\fileManagement\FlySystemManagement::OPTION_FS
                    ));

                return gmdate('Y-m-d\TH:i:s\Z', $filesystem->lastModified($fileLink));
            }
        } catch (\Throwable $exception) {
            $this->logWarning('Unable to resolve media file updatedAt: ' . $exception->getMessage());
        }

        return null;
    }

    /**
     * @param string $link
     * @return \Psr\Http\Message\StreamInterface
     * @throws \core_kernel_persistence_Exception
     * @throws tao_models_classes_FileNotFoundException
     */
    public function getFileStream($link)
    {
        $resource = $this->getResource(tao_helpers_Uri::decode($link));
        $fileLink = $resource->getOnePropertyValue(
            $this->getProperty(TaoMediaOntology::PROPERTY_LINK)
        );

        if (is_null($fileLink)) {
            throw new tao_models_classes_FileNotFoundException($link);
        }

        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        $fileLink = $this->getFileSourceUnserializer()->unserialize($fileLink);

        return $this->getFileManagement()->getFileStream($fileLink);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaBrowser::download
     * @deprecated
     */
    public function download($link)
    {
        $this->logInfo('Deprecated, creates tmpfiles');
        $stream = $this->getFileStream($link);
        $filename = tempnam(sys_get_temp_dir(), 'media');
        $fh = fopen($filename, 'w');
        while (!$stream->eof()) {
            fwrite($fh, $stream->read(1048576));
        }
        fclose($fh);

        $this->tmpFiles[] = $filename;

        return $filename;
    }

    /**
     * @param string $link
     * @return string
     * @throws \core_kernel_persistence_Exception
     * @throws tao_models_classes_FileNotFoundException
     */
    public function getBaseName($link)
    {
        $stream = $this->getFileStream($link);
        $filename = $stream->getMetadata('uri');

        if ($filename === 'php://temp') {
            // We are currently retrieving a remote resource (e.g. on Amazon S3).
            $fileinfo = $this->getFileInfo($link);
            $filename = $fileinfo['link'];
        }

        return basename($filename);
    }

    /**
     * Force the mime-type of a resource
     *
     * @param string $link
     * @param string $mimeType
     * @return boolean
     */
    public function forceMimeType($link, $mimeType)
    {
        $resource = $this->getResource(tao_helpers_Uri::decode($link));
        return $resource->editPropertyValues(
            $this->getProperty(TaoMediaOntology::PROPERTY_MIME_TYPE),
            $mimeType
        );
    }

    /**
     *
     * @param string $path
     * @return \core_kernel_classes_Class
     */
    private function getOrCreatePath($path)
    {
        $rootClass = $this->getRootClass();

        if ($path === '') {
            return $rootClass;
        }

        // If the path is a class URI, returns the existing class.
        $class = $this->getClass(tao_helpers_Uri::decode($path));
        if ($class->isSubClassOf($rootClass) || $class->equals($rootClass) || $class->exists()) {
            return $class;
        }

        // If the given path is a json-encoded array, creates the full path from root class.
        $labels = $this->getArrayFromJson($path);
        if ($labels) {
            return $rootClass->createSubClassPathByLabel($labels);
        }

        // Retrieve or create a direct subclass of the root class.
        return $rootClass->retrieveOrCreateSubClassByLabel($path);
    }

    /**
     * Tries to find a json-encoded array in the given string.
     *
     * If string is actually a json string and a json-encoded array, returns the array.
     * Else, returns false.
     *
     * @param string $string
     * @return array|bool
     */
    private function getArrayFromJson($string)
    {
        $decoded = json_decode($string);

        return $decoded !== null && is_array($decoded)
            ? $decoded
            : false;
    }

    /**
     * Get the service Locator
     *
     * @return ServiceManager
     */
    protected function getServiceLocator()
    {
        return ServiceManager::getServiceManager();
    }

    protected function getRootClassUri(): string
    {
        if ($this->hasOption('rootClass')) {
            $rootClass = $this->getOption('rootClass');

            return $rootClass instanceof \core_kernel_classes_Class
                ? $rootClass->getUri()
                : (string)$rootClass;
        }

        return TaoMediaOntology::CLASS_URI_MEDIA_ROOT;
    }

    protected function getLang()
    {
        return $this->hasOption('lang') ? $this->getOption('lang') : '';
    }

    /**
     * @return MediaService
     */
    protected function getMediaService()
    {
        if (!$this->mediaService) {
            $this->mediaService = MediaService::singleton();
        }
        return $this->mediaService;
    }

    /**
     * @return FileManagement
     */
    protected function getFileManagement()
    {
        if (!$this->fileManagementService) {
            $this->fileManagementService = $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
        }
        return $this->fileManagementService;
    }

    private function removeSchemaFromUriOrLink(string $uriOrLink): string
    {
        return str_replace(self::SCHEME_NAME, '', $uriOrLink);
    }

    public function getProcessedFileStream(string $link): StreamInterface
    {
        return Utils::streamFor(
            $this->getPreparer()->prepare(
                $this->getResource(tao_helpers_Uri::decode($link)),
                $this->getFileStream($link)
            )
        );
    }

    private function getPreparer(): MediaResourcePreparerInterface
    {
        return $this->getServiceLocator()->get(MediaResourcePreparerInterface::SERVICE_ID);
    }

    private function getFileSourceUnserializer(): FileSourceUnserializer
    {
        return $this->getServiceLocator()->get(FileSourceUnserializer::class);
    }

    private function searchDirectories(
        string $parentLink = '',
        array $acceptableMime = [],
        int $depth = 1,
        int $childrenLimit = 0,
        int $childrenOffset = 0
    ): array {

        $class = $this->getClass($parentLink == '' ? $this->getRootClassUri() : tao_helpers_Uri::decode($parentLink));

        $data = $this->getPermissionsMapper()->map(
            [
                'path' => self::SCHEME_NAME . tao_helpers_Uri::encode($class->getUri()),
                'label' => $class->getLabel(),
                'locationPath' => $this->buildLocationPathForClass($class),
                'childrenLimit' => $childrenLimit,
            ],
            $class->getUri()
        );

        $parentClasses = $class->getParentClasses();
        if ($parentClasses !== []) {
            $parentClass = reset($parentClasses);
            if ($parentClass instanceof \core_kernel_classes_Class) {
                $parentUri = $parentClass->getUri();
                if ($parentUri !== '' && $parentUri !== $this->getRootClassUri()) {
                    $data['parentFolderPath'] = self::SCHEME_NAME . tao_helpers_Uri::encode($parentUri);
                }
            }
        }

        if ($depth > 0) {
            $children = [];
            foreach ($class->getSubClasses() as $subclass) {
                $children[] = $this->searchDirectories(
                    $subclass->getUri(),
                    $acceptableMime,
                    $depth - 1,
                    $childrenLimit,
                    $childrenOffset
                );
            }

            $filter = [];

            if (!empty($acceptableMime)) {
                $filter = array_merge($filter, [TaoMediaOntology::PROPERTY_MIME_TYPE => $acceptableMime]);
            }

            $options = array_filter([
                'limit' => $childrenLimit,
                'offset' => $childrenOffset,
            ]);

            foreach ($class->searchInstances($filter, $options) as $instance) {
                try {
                    $children[] = $this->getFileInfo($instance->getUri());
                } catch (tao_models_classes_FileNotFoundException $e) {
                    $this->logEmergency(
                        sprintf(
                            'Encountered issues "%s" while fetching details for %s',
                            $e->getMessage(),
                            $instance->getUri()
                        )
                    );
                }
            }
            $data['children'] = $children;
            $data['total'] = $class->countInstances($filter);
        } else {
            if ($parentLink !== '') {
                $data['path'] = self::SCHEME_NAME . tao_helpers_Uri::encode($parentLink);
            }
            $data['parent'] = $parentLink;
        }

        return $data;
    }

    private function getPermissionsMapper(): MediaSourcePermissionsMapper
    {
        if (!$this->permissionsMapper) {
            $this->permissionsMapper = $this->getServiceLocator()->get(MediaSourcePermissionsMapper::class);
        }

        return $this->permissionsMapper;
    }

    /**
     * Folder path from media root to the given class (labels), for asset location columns.
     */
    private function buildLocationPathForClass(\core_kernel_classes_Class $class): string
    {
        $mediaRootUri = TaoMediaOntology::CLASS_URI_MEDIA_ROOT;
        $segments = [];
        $current = $class;

        while ($current instanceof \core_kernel_classes_Class) {
            $uri = $current->getUri();
            $label = trim($current->getLabel());
            if ($label !== '') {
                array_unshift($segments, $label);
            }
            if ($uri === $mediaRootUri) {
                break;
            }

            $parents = $current->getParentClasses();
            if ($parents === []) {
                break;
            }
            $parent = reset($parents);
            $current = $parent instanceof \core_kernel_classes_Class ? $parent : null;
        }

        return implode('/', $segments);
    }

    public function __destruct()
    {
        foreach ($this->tmpFiles as $tmpFile) {
            if (is_writable($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
