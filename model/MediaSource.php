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
use oat\tao\model\media\MediaManagement;
use oat\tao\model\media\ProcessedFileStreamAware;
use oat\taoMediaManager\model\export\service\MediaResourcePreparer;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use Psr\Http\Message\StreamInterface;
use tao_helpers_Uri;

use function GuzzleHttp\Psr7\stream_for;

class MediaSource extends Configurable implements MediaManagement, ProcessedFileStreamAware
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    public const SCHEME_NAME = 'taomedia://mediamanager/';

    /** @var MediaService */
    protected $mediaService;

    /** @var FileManagement */
    protected $fileManagementService;

    /**
     * Returns the language URI to be used
     * @return string
     */
    protected function getLanguage()
    {
        return $this->hasOption('lang')
            ? $this->getOption('lang')
            : ''
        ;
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
            throw new \tao_models_classes_FileNotFoundException($source);
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

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaBrowser::getDirectory
     */
    public function getDirectory($parentLink = '', $acceptableMime = [], $depth = 1)
    {
        if ($parentLink == '') {
            $class = $this->getClass($this->getRootClassUri());
        } else {
            $class = $this->getClass(tao_helpers_Uri::decode($parentLink));
        }

        $data = [
            'path' => self::SCHEME_NAME . tao_helpers_Uri::encode($class->getUri()),
            'label' => $class->getLabel()
        ];

        if ($depth > 0) {
            $children = [];
            foreach ($class->getSubClasses() as $subclass) {
                $children[] = $this->getDirectory($subclass->getUri(), $acceptableMime, $depth - 1);
            }

            // add a filter for example on language (not for now)
            $filter = [];

            foreach ($class->searchInstances($filter) as $instance) {
                try {
                    $file = $this->getFileInfo($instance->getUri());
                    if (count($acceptableMime) == 0 || in_array($file['mime'], $acceptableMime)) {
                        $children[] = $file;
                    }
                } catch (\tao_models_classes_FileNotFoundException $e) {
                    \common_Logger::e($e->getMessage());
                }
            }
            $data['children'] = $children;
        } else {
            $data['parent'] = $parentLink;
        }
        return $data;
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
        if (!$resource->exists()) {
            throw new \tao_models_classes_FileNotFoundException($link);
        }

        $fileLink = $resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_LINK));
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        $file = null;
        $mime = (string) $resource->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_MIME_TYPE));

        // add the alt text to file array
        $altArray = $resource->getPropertyValues($this->getProperty(MediaService::PROPERTY_ALT_TEXT));
        $alt = $resource->getLabel();
        if (count($altArray) > 0) {
            $alt = $altArray[0];
        }

        $file = [
            'name' => $resource->getLabel(),
            'uri' => self::SCHEME_NAME . tao_helpers_Uri::encode($link),
            'mime' => $mime,
            'size' => $this->getFileManagement()->getFileSize($fileLink),
            'alt' => $alt,
            'link' => $fileLink
        ];

        return $file;
    }

    /**
     * @param string $link
     * @return \Psr\Http\Message\StreamInterface
     * @throws \core_kernel_persistence_Exception
     * @throws \tao_models_classes_FileNotFoundException
     */
    public function getFileStream($link)
    {
        $resource = $this->getResource(tao_helpers_Uri::decode($link));
        $fileLink = $resource->getOnePropertyValue($this->getProperty(MediaService::PROPERTY_LINK));
        if (is_null($fileLink)) {
            throw new \tao_models_classes_FileNotFoundException($link);
        }
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
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
        return $filename;
    }

    /**
     * @param string $link
     * @return string
     * @throws \core_kernel_persistence_Exception
     * @throws \tao_models_classes_FileNotFoundException
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
        return $resource->editPropertyValues($this->getProperty(MediaService::PROPERTY_MIME_TYPE), $mimeType);
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

    protected function getRootClassUri()
    {
        return $this->hasOption('rootClass') ? $this->getOption('rootClass') : MediaService::singleton()->getRootClass();
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
        return stream_for(
            $this->getPreparer()->prepare(
                $this->getResource(tao_helpers_Uri::decode($link)),
                $this->getFileStream($link)
            )
        );
    }

    private function getPreparer(): MediaResourcePreparer
    {
        return $this->getServiceLocator()->get(MediaResourcePreparer::class);
    }
}
