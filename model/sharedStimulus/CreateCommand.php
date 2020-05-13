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

namespace oat\taoMediaManager\model\sharedStimulus;

class CreateCommand
{
    private const DEFAULT_LANGUAGE = 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US';

    /** @var string */
    private $classUri;

    /** @var string */
    private $languageUri;

    /** @var string */
    private $name;

    public function __construct(string $classUri, string $name = null, string $languageUri = null)
    {
        $this->classUri = $classUri;
        $this->name = $name;
        $this->languageUri = $languageUri ?? self::DEFAULT_LANGUAGE;
    }

    public function getClassUri(): string
    {
        return $this->classUri;
    }

    public function getLanguageUri(): string
    {
        return $this->languageUri;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
