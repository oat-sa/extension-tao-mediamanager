<?php
/*  
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
 * Copyright (c) 2002-2008 (original work) Public Research Centre Henri Tudor & University of Luxembourg (under the project TAO & TAO2);
 *               2008-2010 (update and modification) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 * 
 */

namespace oat\taoMediaManager\actions;

use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\ZipExporter;

/**
 * This controller provide the actions to import medias
 */
class MediaExport extends \tao_actions_Export {

    public function __construct(){

        parent::__construct();
        $this->service = MediaService::singleton();
    }


    public function downloadMedia(){
        $uri = $this->getRequestParameter('id');

        $media = new \core_kernel_classes_Resource($uri);
        $filePath = $media->getUniquePropertyValue(new \core_kernel_classes_Property(MEDIA_LINK));

        $fp = fopen($filePath, "r");
        if ($fp !== false) {
            $test =  '<embed src="data:image/gif;base64,';
            while (!feof($fp))
            {
                $test .= base64_encode(fread($fp, filesize($filePath)));
            }
            $test .= '"/>';
            fclose($fp);
        }
        echo $test;
    }

    /**
     * get the main class
     * @return core_kernel_classes_Classes
     */
    protected function getRootClass()
    {
        return new \core_kernel_classes_Class(MEDIA_URI);
    }

    protected function getAvailableExportHandlers() {
        return array(
            new ZipExporter()
        );
    }
}
