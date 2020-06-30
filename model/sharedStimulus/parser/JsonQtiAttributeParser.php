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
use LogicException;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
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
        $xinclude->setAttribute('xml:lang', $this->getLanguage($sharedStimulus->getBody()));

        return $xinclude->toArray();
    }

    private function createDomDocument(SharedStimulus $sharedStimulus): DOMDocument
    {
        $content = $sharedStimulus->getBody();
        if (empty($content)) {
            throw new LogicException('SharedStimulus content is empty and cannot be parsed.');
        }

        $document = new DOMDocument();
        $document->loadXML($content);

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

    private function getLanguage(string $content): ?string
    {
        $langPosition = strpos($content, 'xml:lang=');
        if ($langPosition) {
            return substr($content, $langPosition + 10, 5);
        }

        return $this->getSessionService()->getCurrentSession()->getDataLanguage();
    }

    private function getSessionService(): SessionService
    {
        return $this->getServiceLocator()->get(SessionService::class);
    }
}
