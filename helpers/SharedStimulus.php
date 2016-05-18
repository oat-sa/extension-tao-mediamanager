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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\helpers;

class SharedStimulus
{

    public static function embeddedAsset($filePath)
    {
        $content = file_get_contents($filePath);
        $replacement = \tao_helpers_Uri::url(
            'getFile',
            'MediaManager',
            'taoMediaManager',
            array(
                'uri' => '',
            )
        );

        $content = preg_replace('/taomedia:\/\/mediamanager\/([^"]+)/',$replacement.'${1}', $content);
        $file = \tao_helpers_File::createTempDir().'shared.xml';
        file_put_contents($file, $content);
        return $file;
    }
}