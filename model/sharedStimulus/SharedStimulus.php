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

namespace oat\taoMediaManager\model\sharedStimulus;

use JsonSerializable;

class SharedStimulus implements JsonSerializable
{
    /** @var string */
    private $uri;

    /** @var string */
    private $languageUri;

    /** @var string */
    private $name;

    /** @var string|null */
    private $body;

    public function __construct(string $uri, string $name, string $languageUri, string $body = null)
    {
        $this->uri = $uri;
        $this->name = $name;
        $this->languageUri = $languageUri;
        $this->body = $body;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->uri,
            'languageUri' => $this->languageUri,
            'name' => $this->name,
            'body' => str_replace(PHP_EOL, '', $this->body),
        ];
    }
}
