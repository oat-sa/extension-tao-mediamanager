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
 * Copyright (c) 2021-2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts;

use Throwable;
use oat\oatbox\filesystem\File;
use core_kernel_classes_Literal;
use oat\oatbox\reporting\Report;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\taoMediaManager\model\MediaService;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;

/**
 * sudo -u www-data php index.php 'oat\taoMediaManager\scripts\ReplaceSharedStimulusMedia' [--dryRun]
 */
class ReplaceSharedStimulusMedia extends ScriptAction
{
    public const OPTION_DRY_RUN = 'dryRun';

    /** @var core_kernel_classes_Property */
    private $propertyLink;

    /** @var string */
    private $fileSystemId;

    protected function provideOptions(): array
    {
        return [
            self::OPTION_DRY_RUN => [
                'prefix' => 'd',
                'longPrefix' => self::OPTION_DRY_RUN,
                'flag' => true,
                'defaultValue' => false,
                'description' => 'Get total shared stimulus media and number of shared stimulus media to modify',
            ],
        ];
    }

    protected function provideDescription(): string
    {
        return 'Move shared stimulus stored as single files to sub-folders';
    }

    protected function run(): Report
    {
        $sharedStimulusCount = 0;
        $toBeChangedCount = 0;
        $successCount = 0;
        $notExistsCount = 0;
        $errorsCount = 0;

        $dryRun = $this->hasOption(self::OPTION_DRY_RUN);

        $mediaInstances = $this->getMediaService()->getRootClass()->getInstances(true);
        $report = Report::createInfo(__('%s media on this environment', count($mediaInstances)));

        foreach ($mediaInstances as $mediaInstance) {
            try {
                if (!$this->getSharedStimulusResourceSpecification()->isSatisfiedBy($mediaInstance)) {
                    continue;
                }

                ++$sharedStimulusCount;

                $link = $this->getLinkPropertyValue($mediaInstance);

                if (!$link) {
                    $report->add(Report::createWarning('Link property value is not literal'));

                    continue;
                }

                $report->add(Report::createInfo(__('Current path: %s', $link)));

                if (dirname($link) !== '.') {
                    continue;
                }

                ++$toBeChangedCount;

                if ($dryRun) {
                    continue;
                }

                $sharedStimulusStoredSourceFile = $this->getFile($link);

                if (!$sharedStimulusStoredSourceFile->exists()) {
                    ++$notExistsCount;
                    $report->add(
                        Report::createWarning(
                            __(
                                'Shared stimulus "%s": file "%s" not exists',
                                $mediaInstance->getUri(),
                                $sharedStimulusStoredSourceFile->getBasename()
                            )
                        )
                    );

                    continue;
                }

                $dirname = $this->getStoreService()->store($sharedStimulusStoredSourceFile, $link, []);
                $newMediaLink = $dirname . DIRECTORY_SEPARATOR . $link;
                $report->add(Report::createInfo(__('New path %s', $newMediaLink)));

                if (!$mediaInstance->editPropertyValues($this->getPropertyLink(), $newMediaLink)) {
                    $report->add(Report::createError(__('Issue while modifying %s', $mediaInstance->getUri())));

                    continue;
                }

                $sharedStimulusStoredSourceFile->delete();
                ++$successCount;
            } catch (Throwable $exception) {
                ++$errorsCount;
                $report->add(Report::createError(__('Issue while processing "%s"', $mediaInstance->getUri())));
                $this->logError(
                    sprintf(
                        'Issue while processing "%s": %s',
                        $mediaInstance->getUri(),
                        $exception->getMessage()
                    )
                );
            }
        }

        $report->add(Report::createInfo(__('Total shared stimulus media: %s', $sharedStimulusCount)));
        $report->add(Report::createInfo(__('%s shared stimulus media to modify', $toBeChangedCount)));

        if (!$dryRun) {
            $report->add(Report::createError(__('%s errors while processing shared stimulus media', $errorsCount)));
            $report->add(Report::createWarning(__('%d shared stimulus media not exists', $notExistsCount)));
            $report->add(Report::createSuccess(__('%s media successfully modified', $successCount)));
        }

        return $report;
    }

    private function getLinkPropertyValue(core_kernel_classes_Resource $mediaInstance): ?string
    {
        $propertyValue = $mediaInstance->getUniquePropertyValue($this->getPropertyLink());

        if ($propertyValue instanceof core_kernel_classes_Literal) {
            return $this->getFileSourceUnserializer()->unserialize((string) $propertyValue);
        }

        return null;
    }

    private function getFile($link): File
    {
        return $this->getFileSystemService()
            ->getDirectory($this->getFileSystemId())
            ->getFile($link);
    }

    private function getPropertyLink(): core_kernel_classes_Property
    {
        if (!isset($this->propertyLink)) {
            $this->propertyLink = $this->getOntology()->getProperty(MediaService::PROPERTY_LINK);
        }

        return $this->propertyLink;
    }

    private function getOntology(): Ontology
    {
        return $this->getServiceManager()->get(Ontology::SERVICE_ID);
    }

    private function getMediaService(): MediaService
    {
        return $this->getServiceManager()->get(MediaService::class);
    }

    private function getSharedStimulusResourceSpecification(): SharedStimulusResourceSpecification
    {
        return $this->getServiceManager()->get(SharedStimulusResourceSpecification::class);
    }

    private function getStoreService(): StoreService
    {
        return $this->getServiceManager()->get(StoreService::class);
    }

    private function getFileSourceUnserializer(): FileSourceUnserializer
    {
        return $this->getServiceManager()->get(FileSourceUnserializer::class);
    }

    private function getFileSystemId(): string
    {
        if (!isset($this->fileSystemId)) {
            $flySystemManagement = $this->getServiceManager()->get(FlySystemManagement::SERVICE_ID);

            $this->fileSystemId = $flySystemManagement->getOption(FlySystemManagement::OPTION_FS);
        }

        return $this->fileSystemId;
    }

    private function getFileSystemService(): FileSystemService
    {
        return $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
    }
}
