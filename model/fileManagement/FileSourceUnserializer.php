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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\fileManagement;

use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\oatbox\service\ConfigurableService;

class FileSourceUnserializer extends ConfigurableService
{

    private const FILE_PREFIX = 'file://';
    private const MEDIA_MANAGER_FOLDER = 'mediaManager';

    public function unserialize(string $link): string
    {
        if (strpos($link, self::FILE_PREFIX . self::MEDIA_MANAGER_FOLDER) !== false) {
            $unserializedFile = $this->getFileRefSerializer()->unserializeFile($link);
            $link = (string)$unserializedFile->getPrefix();
        }

        return $link;
    }

    private function getFileRefSerializer(): FileReferenceSerializer
    {
        return $this->getServiceLocator()->get(FileReferenceSerializer::SERVICE_ID);
    }
}
