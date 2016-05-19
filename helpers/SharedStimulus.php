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

    public static function prepareExportedFile(&$file, $resolver)
    {
        $fileContent = file_get_contents($file);
        $files = array();
        $replacements = array();
        $out = array();
        preg_match_all('/taomedia:\/\/mediamanager\/[^"]+/', $fileContent, $out, PREG_SET_ORDER);
        foreach($out as $key => $matches){
            try{
                $replacement = $matches[0];
                $mediaAsset = $resolver->resolve($matches[0]);
                $mediaSource = $mediaAsset->getMediaSource();
                if (get_class($mediaSource) !== 'oat\tao\model\media\sourceStrategy\HttpSource') {
                    $srcPath = $mediaSource->download($mediaAsset->getMediaIdentifier());
                    $fileInfo = $mediaSource->getFileInfo($mediaAsset->getMediaIdentifier());
                    $replacement = $mediaAsset->getMediaIdentifier();

                    if(isset($fileInfo['filePath'])){
                        $filename = $fileInfo['filePath'];
                        if($mediaAsset->getMediaIdentifier() !== $fileInfo['uri']){
                            $replacement = $filename;
                        }
                        $destPath = ltrim($filename,'/');
                    } else {
                        $destPath = $replacement = basename($srcPath);
                    }
                    if (file_exists($srcPath)) {
                        $files[$destPath] = $srcPath;
                    }
                }
            } catch(\tao_models_classes_FileNotFoundException $e){
                $replacement = '';
            }
            $replacements[$matches[0]] = $replacement;
        }
        foreach($replacements as $base => $final){
            $fileContent = str_replace($base, $final, $fileContent);
        }
        $file = \tao_helpers_File::createTempDir().'shared.xml';
        file_put_contents($file, $fileContent);
        return $files;

    }
}