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

namespace oat\taoMediaManager\model\sharedStimulus\css;

class SaveCommand
{
    /** @var string */
    private $uri;

    /** @var string */
    private $stylesheetUri;

    /* @var array */
    private $cssClassesArray;

    /** @var string */
    private $lang;

    public function __construct(string $uri, string $stylesheetUri, array $cssClassesArray, string $lang = null)
    {
        $this->uri = $uri;
        $this->stylesheetUri = $stylesheetUri;
        $this->cssClassesArray = $cssClassesArray;
        $this->lang = $lang;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getStylesheetUri(): string
    {
        return $this->stylesheetUri;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function getCssClassesArray(): array
    {
        return $this->cssClassesArray;
    }
}
