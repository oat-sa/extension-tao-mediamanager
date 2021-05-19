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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\css\service;

use Psr\Http\Message\StreamInterface;
use oat\taoMediaManager\model\sharedStimulus\css\GetStylesheetsCommand;
use oat\taoMediaManager\model\sharedStimulus\css\LoadStylesheetCommand;

class StylesheetService extends ConfigurableCssService
{
    private const STYLESHEETS_DIRECTORY = 'css';

    public function getList(GetStylesheetsCommand $command): array
    {
        $path = $this->getPath($command);
        $list = $this->getFileSystem()->listContents($path . DIRECTORY_SEPARATOR . self::STYLESHEETS_DIRECTORY);

        return array_column($list, 'basename');
    }

    public function load(LoadStylesheetCommand $command): StreamInterface
    {
        $path = $this->getPath($command);
        $stylesheet = $command->getStylesheet();
        $link = $path . DIRECTORY_SEPARATOR . self::STYLESHEETS_DIRECTORY . DIRECTORY_SEPARATOR . $stylesheet;

        return $this->getFileManagement()->getFileStream($link);
    }
}
