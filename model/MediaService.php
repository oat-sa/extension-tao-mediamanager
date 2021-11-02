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
 * Copyright (c) 2014-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model;

use common_ext_ExtensionsManager;
use core_kernel_classes_Literal;
use core_kernel_classes_Resource as RdfResource;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\File;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\ClassServiceTrait;
use oat\tao\model\GenerisServiceTrait;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\relation\event\MediaRemovedEvent;
use oat\taoMediaManager\model\relation\event\MediaSavedEventDispatcher;
use oat\taoRevision\model\RepositoryInterface;
use tao_helpers_File;

/**
 * Service methods to manage the Media
 *
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 */
class MediaService extends ConfigurableService
{
    use ClassServiceTrait;
    use GenerisServiceTrait;
    use LoggerAwareTrait;

    public const ROOT_CLASS_URI = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Media';

    public const PROPERTY_LINK = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link';

    public const PROPERTY_LANGUAGE = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Language';

    public const PROPERTY_ALT_TEXT = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AltText';

    public const PROPERTY_MD5 = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#md5';

    public const PROPERTY_MIME_TYPE = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#mimeType';

    public const SHARED_STIMULUS_MIME_TYPE = 'application/qti+xml';

    public const MEDIA_ALLOWED_TYPES = [
        'application/xml',
        'text/xml',
        MediaService::SHARED_STIMULUS_MIME_TYPE
    ];

    /**
     * @deprecated
     */
    public static function singleton()
    {
        return ServiceManager::getServiceManager()->get(self::class);
    }

    /**
     * (non-PHPdoc)
     * @see \tao_models_classes_ClassService::getRootClass()
     */
    public function getRootClass()
    {
        return $this->getClass(self::ROOT_CLASS_URI);
    }

    /**
     * Create a media instance from a file, and define its class and language
     *
     * @param string|File $fileSource path to the file to create instance from
     * @param string $classUri parent to add the instance to
     * @param string $language language of the content
     * @param string $label label of the instance
     * @param string $mimeType mimeType of the file
     * @param string|null $userId owner of the resource
     * @return string | bool $instanceUri or false on error
     */
    public function createMediaInstance(
        $fileSource,
        $classUri,
        $language,
        $label = null,
        $mimeType = null,
        $userId = null
    ) {
        $link = $this->getFileManager()->storeFile($fileSource, $label);

        if ($link === false) {
            return false;
        }

        $clazz = $this->getClass($classUri);

        //create media instance
        if (is_null($label)) {
            $label = $fileSource instanceof File ? $fileSource->getBasename() : basename($fileSource);
        }

        $content = $fileSource instanceof File ? $fileSource->read() : file_get_contents($fileSource);

        if (is_null($mimeType)) {
            $mimeType = $fileSource instanceof File ? $fileSource->getMimeType() : tao_helpers_File::getMimeType(
                $fileSource
            );
        }

        $properties = [
            OntologyRdfs::RDFS_LABEL => $label,
            self::PROPERTY_LINK => $link,
            self::PROPERTY_LANGUAGE => $language,
            self::PROPERTY_MD5 => md5($content),
            self::PROPERTY_MIME_TYPE => $mimeType,
            self::PROPERTY_ALT_TEXT => $label
        ];

        $instance = $clazz->createInstanceWithProperties($properties);
        $id = $instance->getUri();

        $this->dispatchMediaSavedEvent('Initial import', $instance, $link, $mimeType, $userId, $content);

        return $id;
    }

    public function createSharedStimulusInstance(
        string $link,
        string $classUri,
        string $language,
        string $userId = null
    ): string {
        $content = $this->getFileManager()->getFileStream($link)->getContents();
        $clazz = $this->getClass($classUri);
        $label = basename($link);

        $properties = [
            OntologyRdfs::RDFS_LABEL => $label,
            self::PROPERTY_LINK => $link,
            self::PROPERTY_LANGUAGE => $language,
            self::PROPERTY_MD5 => md5($content),
            self::PROPERTY_MIME_TYPE => self::SHARED_STIMULUS_MIME_TYPE,
            self::PROPERTY_ALT_TEXT => $label,
        ];

        $instance = $clazz->createInstanceWithProperties($properties);
        $id = $instance->getUri();

        $this->dispatchMediaSavedEvent('Initial import', $instance, $link, self::SHARED_STIMULUS_MIME_TYPE, $userId, $content);

        return $id;
    }

    /**
     * Edit a media instance with a new file and/or a new language
     *
     * @param string|File $fileSource
     */
    public function editMediaInstance(
        $fileSource,
        string $id,
        string $language = null,
        string $userId = null
    ): bool {
        $instance = $this->getResource($id);
        $link = $this->getLink($instance);
        $fileManager = $this->getFileManager();
        $fileManager->deleteFile($link);
        $link = $fileManager->storeFile($fileSource, $instance->getLabel());

        if ($link !== false) {
            $md5 = $fileSource instanceof File ? md5($fileSource->read()) : md5_file($fileSource);

            $instance->editPropertyValues($this->getProperty(self::PROPERTY_LINK), $link);
            $instance->editPropertyValues($this->getProperty(self::PROPERTY_MD5), $md5);

            if ($language) {
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_LANGUAGE), $language);
            }

            $this->dispatchMediaSavedEvent('Imported new file', $instance, $fileSource, $this->getResourceMimeType($instance), $userId);
        }

        return $link !== false;
    }

    public function deleteResource(RdfResource $resource)
    {
        $link = $this->getLink($resource);

        if ($this->removeFromFilesystem($link) && $resource->delete()) {
            $this->getEventManager()
                ->trigger(new MediaRemovedEvent($resource->getUri()));

            return true;
        }

        return false;
    }

    /**
     * @param string|File $link
     */
    public function dispatchMediaSavedEvent(
        string $commitMessage,
        RdfResource $instance,
        $link,
        string $mimeType,
        string $userId = null,
        string $content = null
    ): void {
        $eventDispatcher = $this->getMediaSavedEventDispatcher();
        if ($content) {
            $eventDispatcher->dispatchFromContent($instance->getUri(), $mimeType, $content);
        } else {
            $eventDispatcher->dispatchFromFile($instance->getUri(), $link, $this->getResourceMimeType($instance));
        }

        if ($this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID)->isEnabled('taoRevision')) {
            $this->logInfo('Auto generating initial revision');
            $this->getRepositoryService()->commit($instance, __($commitMessage), null, $userId);
        }
    }

    /**
     * Checks if the given mime type is an allowed type for uploaded files.
     *
     * @param string|null $type The mime type to check
     * @return bool
     */
    public function isXmlAllowedMimeType(string $type): bool
    {
        $paramsPos = strpos($type, ';');
        if ($paramsPos > 0) {
            $type = substr($type, 0, $paramsPos);
        }

        return in_array($type, self::MEDIA_ALLOWED_TYPES, true);
    }

    private function removeFromFilesystem($link): bool
    {
        $directory = dirname($link);

        if ($directory !== '.') {
            return $this->getFileManager()->deleteDirectory($directory);
        }

        return $this->getFileManager()->deleteFile($link);
    }

    private function getLink(RdfResource $resource): string
    {
        $instance = $resource->getUniquePropertyValue($this->getProperty(self::PROPERTY_LINK));

        $link = $instance instanceof RdfResource ? $instance->getUri() : (string)$instance;
        return $this->getFileSourceUnserializer()->unserialize($link);
    }

    private function getMediaSavedEventDispatcher(): MediaSavedEventDispatcher
    {
        return $this->getServiceLocator()->get(MediaSavedEventDispatcher::class);
    }

    private function getFileManager(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }

    private function getRepositoryService(): RepositoryInterface
    {
        return $this->getServiceLocator()->get(RepositoryInterface::SERVICE_ID);
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    private function getResourceMimeType(RdfResource $resource): ?string
    {
        $container = $resource->getUniquePropertyValue($resource->getProperty(MediaService::PROPERTY_MIME_TYPE));

        if ($container instanceof core_kernel_classes_Literal) {
            $mimeType = (string)$container;

            return $mimeType === MediaService::SHARED_STIMULUS_MIME_TYPE ? $mimeType : null;
        }

        return null;
    }

    private function getFileSourceUnserializer(): FileSourceUnserializer
    {
        return $this->getServiceLocator()->get(FileSourceUnserializer::class);
    }
}
