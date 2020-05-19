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
 *
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\renderer;

use DOMDocument;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoQtiItem\model\qti\ParserFactory;
use oat\taoQtiItem\model\qti\XInclude;

class JsonQtiAttributeRenderer extends ConfigurableService
{
    public function render(SharedStimulus $sharedStimulus)
    {
        $document = $this->createDomDocument($sharedStimulus);
        $xinclude = $this->createXInclude($document);

        return $xinclude->toArray();
    }

    private function createDomDocument(SharedStimulus $sharedStimulus) : DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML($sharedStimulus->getBody());

        return $document;
    }

    private function createXInclude(DOMDocument $document): XInclude
    {
        return $this->hydrateXInclude(new XInclude(), $document->firstChild);
    }

    private function hydrateXInclude(XInclude $xinclude, DOMDocument $document)
    {
        $parser = new ParserFactory($document);
        $parser->loadContainerStatic($document->firstChild, $xinclude->getBody());

        return $xinclude;
    }
}
