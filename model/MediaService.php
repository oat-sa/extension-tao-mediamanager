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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\model;

use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\filesystem\File;
use common_ext_ExtensionsManager;
use oat\taoRevision\model\RevisionService;
use oat\taoMediaManager\model\fileManagement\FileManagement;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoMediaManager
 */
class MediaService extends \tao_models_classes_ClassService
{
    const ROOT_CLASS_URI = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Media';

    const PROPERTY_LINK = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link';
    const PROPERTY_LANGUAGE = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Language';
    const PROPERTY_ALT_TEXT = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AltText';
    const PROPERTY_MD5 =  'http://www.tao.lu/Ontologies/TAOMedia.rdf#md5';
    const PROPERTY_MIME_TYPE = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#mimeType';

    use OntologyAwareTrait;

    /**
     * @var FileManagement
     */
    protected $fileManager;

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
     * @return string | bool $instanceUri or false on error
     */
    public function createMediaInstance($fileSource, $classUri, $language, $label = null, $mimeType = null)
    {
        $clazz = $this->getClass($classUri);

        //create media instance
        if (is_null($label)) {
            $label = $fileSource instanceof File ? $fileSource->getBasename() : basename($fileSource);
        }
        $md5 = $fileSource instanceof File ? md5($fileSource->read()) : md5_file($fileSource);

        $link = $this->getFileManager()->storeFile($fileSource, $label);

        if ($link !== false) {
            if (is_null($mimeType)) {
                $mimeType = $fileSource instanceof File ? $fileSource->getMimeType() : \tao_helpers_File::getMimeType($fileSource);
            }
            $instance = $clazz->createInstanceWithProperties(array(
                OntologyRdfs::RDFS_LABEL => $label,
                self::PROPERTY_LINK => $link,
                self::PROPERTY_LANGUAGE => $language,
                self::PROPERTY_MD5 => $md5,
                self::PROPERTY_MIME_TYPE => $mimeType,
                self::PROPERTY_ALT_TEXT => $label
            ));

            if ($this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID)->isEnabled('taoRevision')) {
                \common_Logger::i('Auto generating initial revision');
                RevisionService::commit($instance, __('Initial import'));
            }
            return $instance->getUri();
        }
        return false;
    }

    /**
     * Edit a media instance with a new file and/or a new language
     *
     * @param string|File $fileSource
     * @param string $instanceUri
     * @param string $language
     * @return bool $instanceUri or false on error
     */
    public function editMediaInstance($fileSource, $instanceUri, $language)
    {
        $instance = $this->getResource($instanceUri);
        $link = $this->getLink($instance);

        $fileManager = $this->getFileManager();
        $fileManager->deleteFile($link);
        $link = $fileManager->storeFile($fileSource, $instance->getLabel());

        if ($link !== false) {
            $md5 = $fileSource instanceof File ? md5($fileSource->read()) : md5_file($fileSource);
            /** @var $instance  \core_kernel_classes_Resource */
            if (!is_null($instance) && $instance instanceof \core_kernel_classes_Resource) {
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_LINK), $link);
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_LANGUAGE), $language);
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_MD5), $md5);
            }
            
            if ($this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID)->isEnabled('taoRevision')) {
                \common_Logger::i('Auto generating revision');
                RevisionService::commit($instance, __('Imported new file'));
            }
        }
        return ($link !== false) ? true : false;

    }
    
    /**
     * (non-PHPdoc)
     * @see \tao_models_classes_ClassService::deleteResource()
     */
    public function deleteResource(\core_kernel_classes_Resource $resource)
    {
        $link = $this->getLink($resource);
        return parent::deleteResource($resource) && $this->getFileManager()->deleteFile($link);
    }
    
    /**
     * Returns the link of a media resource
     * 
     * @param \core_kernel_classes_Resource $resource
     * @return string
     */
    protected function getLink(\core_kernel_classes_Resource $resource)
    {
        $instance = $resource->getUniquePropertyValue($this->getProperty(self::PROPERTY_LINK));
        return $instance instanceof \core_kernel_classes_Resource ? $instance->getUri() : (string)$instance;
    }

    /**
     * @return FileManagement
     */
    protected function getFileManager()
    {
        if (!$this->fileManager) {
            $this->fileManager = $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
        }
        return $this->fileManager;
    }
}
