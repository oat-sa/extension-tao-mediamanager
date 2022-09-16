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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\css\dto;

class UploadedStylesheet
{
    /** @var string */
    private $uri;

    /** @var string */
    private $fileName;

    /* @var string */
    private $tmpFileLink;

    public function __construct(string $uri, string $fileName, string $tmpFileLink)
    {
        $this->uri = $uri;
        $this->fileName = $fileName;
        $this->tmpFileLink = $tmpFileLink;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getTmpFileLink(): string
    {
        return $this->tmpFileLink;
    }

    /**
     * @return false|resource
     */
    public function getFileResource()
    {
        return fopen($this->tmpFileLink, 'r');
    }
}
