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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\media\service;

use common_Exception;
use core_kernel_classes_EmptyProperty;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\generis\model\fileReference\ResourceFileSerializer;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\service\ConfigurableService;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;

class QtiItemXmlRetriever extends ConfigurableService
{
    private const ITEM_CONTENT = 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent';

    use OntologyAwareTrait;

    /**
     * @throws XmlStorageException
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    public function retrieve(string $id): XmlDocument
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadFromString($this->getFileContent($id));

        return $xmlDocument;
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    private function getFileContent(string $id): string
    {
        /** @var Directory $file */
        $file = $this->getFileReferenceSerializer()->unserialize($this->getItemPath($id));

        return $file->getFile('qti.xml')->read();
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    private function getItemPath(string $id): string
    {
        $itemResource = $this->getModel()->getResource($id);

        return (string)$itemResource->getUniquePropertyValue($itemResource->getProperty(self::ITEM_CONTENT));
    }

    private function getFileReferenceSerializer(): FileReferenceSerializer
    {
        return $this->getServiceLocator()->get(ResourceFileSerializer::SERVICE_ID);
    }
}
