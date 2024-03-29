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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\model\fileManagement;

use oat\oatbox\filesystem\File;
use Psr\Http\Message\StreamInterface;

/**
 * Interface to manage the storage of the taoMediaManager files
 */
interface FileManagement
{
    public const SERVICE_ID = 'taoMediaManager/fileManager';

    /**
     * @param string|File $filePath the relative path to the file
     * @return string a link to the file in order to retrieve it later
     */
    public function storeFile($filePath, $label);

    /**
     * Returns the Size of the file
     *
     * @param string $link
     * @return string size of file in bytes
     */
    public function getFileSize($link);

    /**
     * Returns a stream of the file content
     *
     * @param string $link
     * @return StreamInterface
     */
    public function getFileStream($link);

    /**
     * @param $link
     * @return boolean if the deletion was successful or not
     */
    public function deleteFile($link);
}
