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

namespace oat\taoMediaManager\model\sharedStimulus\parser;

use DOMDocument;
use DOMElement;
use LogicException;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoQtiItem\model\qti\exception\QtiModelException;
use oat\taoQtiItem\model\qti\ParserFactory;
use oat\taoQtiItem\model\qti\XInclude;

class JsonQtiAttributeParser extends ConfigurableService
{
    /**
     * @throws QtiModelException
     */
    public function parse(SharedStimulus $sharedStimulus): array
    {
        $document = $this->createDomDocument($sharedStimulus);
        $xinclude = $this->createXInclude($document);
        $this->addLanguageAttribute($document, $xinclude);

        return $xinclude->toArray();
    }

    private function createDomDocument(SharedStimulus $sharedStimulus): DOMDocument
    {
        $content = $sharedStimulus->getBody();
        if (empty($content)) {
            throw new LogicException('SharedStimulus content is empty and cannot be parsed.');
        }

        $document = new DOMDocument();
        $document->loadXML($content, LIBXML_BIGLINES | LIBXML_PARSEHUGE);

        return $document;
    }

    private function createXInclude(DOMDocument $document): XInclude
    {
        return $this->hydrateXInclude(new XInclude(), $document);
    }

    private function hydrateXInclude(XInclude $xinclude, DOMDocument $document): XInclude
    {
        $parser = new ParserFactory($document);
        $parser->loadContainerStatic($document->firstChild, $xinclude->getBody());

        return $xinclude;
    }

    /**
     * @throws QtiModelException
     */
    private function addLanguageAttribute(DOMDocument $document, XInclude $xinclude): void
    {
        $rootNode = $document->firstChild;
        $languageAttribute = trim($rootNode->getAttribute('xml:lang'));

        if (strlen($languageAttribute) < 2) {
            $this->getLogger()->notice(
                'lang attribute is empty. Impossible to set the Language Attribute',
                [
                    'document' => substr($document->saveXML($rootNode), 0, 200)
                ]
            );

            return;
        }

        $xinclude->setAttribute(
            'xml:lang',
            $languageAttribute
        );
    }
}
