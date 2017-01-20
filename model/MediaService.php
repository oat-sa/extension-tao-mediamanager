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

use oat\taoMediaManager\model\fileManagement\FileManager;
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

    /**
     * (non-PHPdoc)
     * @see tao_models_classes_ClassService::getRootClass()
     */
    public function getRootClass()
    {
        return $this->getClass(self::ROOT_CLASS_URI);
    }


    /**
     * Create a media instance from a file, and define its class and language
     *
     * @param string $fileSource path to the file to create instance from
     * @param string $classUri parent to add the instance to
     * @param string $language language of the content
     * @param string $label label of the instance
     * @param string $mimeType mimeType of the file
     * @return string | bool $instanceUri or false on error
     */
    public function createMediaInstance($fileSource, $classUri, $language, $label = null, $mimeType = null)
    {
        $clazz = new \core_kernel_classes_Class($classUri);

        //get the file MD5
        $md5 = md5_file($fileSource);

        //create media instance
        $label = is_null($label) ? basename($fileSource) : $label;
        $fileManager = FileManager::getFileManagementModel();
        $link = $fileManager->storeFile($fileSource, $label);

        if ($link !== false) {
            $mimeType = is_null($mimeType) ? \tao_helpers_File::getMimeType($fileSource) : $mimeType;
            $instance = $clazz->createInstanceWithProperties(array(
                RDFS_LABEL => $label,
                self::PROPERTY_LINK => $link,
                self::PROPERTY_LANGUAGE => $language,
                self::PROPERTY_MD5 => $md5,
                self::PROPERTY_MIME_TYPE => $mimeType,
                self::PROPERTY_ALT_TEXT => $label
            ));

            if (common_ext_ExtensionsManager::singleton()->isEnabled('taoRevision')) {
                \common_Logger::i('Auto generating initial revision');
                RevisionService::commit($instance, __('Initial import'));
            }
            return $instance->getUri();
        }
        return false;
    }

    /**
     * Edit a media instance with a new file and/or a new language
     * @param $fileTmp
     * @param $instanceUri
     * @param $language
     * @return bool $instanceUri or false on error
     */
    public function editMediaInstance($fileTmp, $instanceUri, $language)
    {
        $instance = new \core_kernel_classes_Resource($instanceUri);
        $link = $this->getLink($instance);

        $fileManager = FileManager::getFileManagementModel();
        $fileManager->deleteFile($link);
        $link = $fileManager->storeFile($fileTmp, $instance->getLabel());

        if ($link !== false) {
            //get the file MD5
            $md5 = md5_file($fileTmp);
            /** @var $instance  \core_kernel_classes_Resource */
            if (!is_null($instance) && $instance instanceof \core_kernel_classes_Resource) {
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_LINK), $link);
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_LANGUAGE), $language);
                $instance->editPropertyValues($this->getProperty(self::PROPERTY_MD5), $md5);
            }
            
            if (common_ext_ExtensionsManager::singleton()->isEnabled('taoRevision')) {
                \common_Logger::i('Auto generating revision');
                RevisionService::commit($instance, __('Imported new file'));
            }
        }
        return ($link !== false) ? true : false;

    }
    
    /**
     * (non-PHPdoc)
     * @see tao_models_classes_ClassService::deleteResource()
     */
    public function deleteResource(\core_kernel_classes_Resource $resource)
    {
        $link = $this->getLink($resource);
        $fileManager = $this->getServiceManager()->get(FileManagement::SERVICE_ID);
        return parent::deleteResource($resource) && $fileManager->deleteFile($link);
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
}
