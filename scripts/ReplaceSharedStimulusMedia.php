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

namespace oat\taoMediaManager\scripts;

use core_kernel_classes_Literal;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaService;
use core_kernel_classes_Resource as RdfResource;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;

/**
 * Used to update old media from the command line
 *
 * ```
 * sudo -u www-data php index.php 'oat\taoMediaManager\scripts\ReplaceSharedStimulusMedia' [dryRun]
 * ```
 */
class ReplaceSharedStimulusMedia extends ConfigurableService implements Action
{
    use OntologyAwareTrait;

    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        $mediaInstances = $this->getMediaService()->getRootClass()->getInstances(true);

        $report = Report::createInfo(__('%s media on this environment', count($mediaInstances)));
        $sharedStimulusCount = 0;
        $toBeChangedCount = 0;
        $successCount = 0;

        $dryRun = isset($params[0]) && $params[0] === 'dryRun';

        foreach ($mediaInstances as $mediaInstance) {
            if (!$this->getSharedStimulusResourceSpecification()->isSatisfiedBy($mediaInstance)) {
                continue;
            }

            $sharedStimulusCount++;

            $link = $this->getLinkPropertyValue($mediaInstance);
            if (!$link) {
                $report->add(Report::createWarning('Link property value is not literal'));
                continue;
            }

            $report->add(Report::createInfo(__('Current path: %s', $link)));
            $sharedStimulusStoredSourceFile = $this->getFile($link);

            if (dirname($link) === '.') {
                $toBeChangedCount++;

                if (!$dryRun) {
                    $dirname = $this->getSharedStimulusStoreService()->store($sharedStimulusStoredSourceFile, $link, []
                    );
                    $newMediaLink = $dirname . DIRECTORY_SEPARATOR . $link;
                    $report->add(Report::createInfo(__('New path %s', $newMediaLink)));
                    if ($mediaInstance->editPropertyValues(
                        $this->getProperty(MediaService::PROPERTY_LINK),
                        $newMediaLink
                    )) {
                        $sharedStimulusStoredSourceFile->delete();
                        $successCount++;
                    } else {
                        $report->add(Report::createError(__('Issue while modifying %s', $mediaInstance->getUri())));
                    }
                }
            }
        }

        $report->add(Report::createSuccess(__('%s shared stimulus media to modify', $toBeChangedCount)));
        $report->add(Report::createInfo(__('Total shared stimulus media: %s', $sharedStimulusCount)));


        if (!$dryRun) {
            $report->add(Report::createSuccess(__('%s media successfully modified', $successCount)));
        }

        return $report;
    }

    private function getLinkPropertyValue(RdfResource $mediaInstance): ?string
    {
        $propertyValue = $mediaInstance->getUniquePropertyValue($this->getProperty(MediaService::PROPERTY_LINK));
        if ($propertyValue instanceof core_kernel_classes_Literal) {
            return $this->getFileSourceUnserializer()->unserialize((string)$propertyValue);
        }

        return null;
    }

    private function getFile($link): File
    {
        return $this->getFileSystemService()
            ->getDirectory($this->getFlySystemManagement()->getOption(FlySystemManagement::OPTION_FS))
            ->getFile($link);
    }

    private function getMediaService(): MediaService
    {
        return $this->getServiceLocator()->get(MediaService::class);
    }

    private function getSharedStimulusResourceSpecification(): SharedStimulusResourceSpecification
    {
        return $this->getServiceLocator()->get(SharedStimulusResourceSpecification::class);
    }

    private function getSharedStimulusStoreService(): StoreService
    {
        return $this->getServiceLocator()->get(StoreService::class);
    }

    private function getFileSourceUnserializer(): FileSourceUnserializer
    {
        return $this->getServiceLocator()->get(FileSourceUnserializer::class);
    }

    private function getFlySystemManagement(): FlySystemManagement
    {
        return $this->getServiceLocator()->get(FlySystemManagement::SERVICE_ID);
    }

    private function getFileSystemService(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }
}

