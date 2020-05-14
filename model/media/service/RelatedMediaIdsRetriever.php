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
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use qtism\data\storage\xml\XmlStorageException;
use qtism\data\XInclude;

class RelatedMediaIdsRetriever extends ConfigurableService
{
    private const ITEM_CONTENT = 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent';

    use OntologyAwareTrait;

    /**
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     * @throws XmlStorageException
     */
    public function retrieveIds(string $id): array
    {
        $xmlDocument = $this->getQtiItemXmlRetriever()->retrieve($id);

        $xIncludes = $xmlDocument->getDocumentComponent()
            ->getComponentsByClassName('include', true);

        $out = [];

        /** @var XInclude $include */
        foreach ($xIncludes as $include) {
            $out[] = $include->getHref();
        }

        return $out;
    }

    private function getQtiItemXmlRetriever(): QtiItemXmlRetriever
    {
        return $this->getServiceLocator()->get(QtiItemXmlRetriever::class);
    }
}
