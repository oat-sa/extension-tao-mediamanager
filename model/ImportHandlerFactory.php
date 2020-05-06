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

namespace oat\taoMediaManager\model;

use oat\generis\model\data\Ontology;
use tao_models_classes_import_ImportHandler;
use Throwable;

class ImportHandlerFactory
{
    /** @var Ontology */
    private $ontology;

    public function __construct(Ontology $ontology)
    {
        $this->ontology = $ontology;
    }

    /**
     * @return tao_models_classes_import_ImportHandler[]
     */
    public function createAvailable(): array
    {
        return [
            new FileImporter(),
            new SharedStimulusImporter()
        ];
    }

    public function createByMediaId(string $id): tao_models_classes_import_ImportHandler
    {
        return $this->isQtiMedia($id) ? new SharedStimulusImporter($id) : new FileImporter($id);
    }

    private function isQtiMedia(string $id): bool
    {
        try {
            $class = $this->ontology->getClass($id);

            $mimeType = $class->getProperty('http://www.tao.lu/Ontologies/TAOMedia.rdf#mimeType');

            return (string)$class->getOnePropertyValue($mimeType) === 'application/qti+xml';
        } catch (Throwable $exception) {
            return false;
        }
    }
}
