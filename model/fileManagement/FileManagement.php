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


interface FileManagement {


    /**
     * @param string $filePath the relative path to the file
     * @return string a link to the file in order to retrieve it later
     */
    public function storeFile($filePath, $label);

    /**
     *
     * @param string $link the link provided by storeFile
     * @return string the file that match the link
     */
    public function retrieveFile($link);

    /**
     * @param $link
     * @return boolean if the deletion was successful or not
     */
    public function deleteFile($link);

} 