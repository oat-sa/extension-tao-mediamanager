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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoMediaManager\scripts;

use oat\generis\model\GenerisRdf;
use oat\oatbox\action\Action;
use common_report_Report as Report;
use oat\taoMediaManager\model\MediaService;

/**
 * Class UpdateMedia
 *
 * Used to update old media from the command line
 *
 * ```
 * sudo -u www-data php index.php 'oat\taoMediaManager\scripts\UpdateMedia' [wetrun]
 * ```
 */
class UpdateMedia implements Action
{

    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        $mediaInstances = MediaService::singleton()->getRootClass()->getInstances(true);
        /** @var \core_kernel_classes_Resource $mediaInstance */
        $report = Report::createSuccess(__('%s media on this environment', count($mediaInstances)));
        $count = 0;
        $success = 0;

        $dryrun = (isset($params[0]) && strpos($params[0], 'wetrun') !== false) ? false : true;

        foreach ($mediaInstances as $mediaInstance){
            $link = $mediaInstance->getUniquePropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK));

            if($link instanceof \core_kernel_classes_Resource){
                $count++;
                $filename = $link->getUniquePropertyValue(new \core_kernel_classes_Property(GenerisRdf::PROPERTY_FILE_FILENAME));
                $filename = $filename instanceof \core_kernel_classes_Resource ? $filename->getUri() : (string)$filename;

                if(!$dryrun){
                  if($mediaInstance->editPropertyValues(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK), $filename)){
                      $success++;
                  } else {
                      $report->add(Report::createFailure(__('Issue while modifying %s', $mediaInstance->getUri())));
                  }
                }
            }
        }

        $report->add(Report::createSuccess(__('%s media to modify', $count)));
        if(!$dryrun){
            $report->add(Report::createSuccess(__('%s media successfully modified', $success)));
        }

        return $report;
    }

}