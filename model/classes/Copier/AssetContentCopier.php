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
 * Copyright (c) 2022-2024 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\classes\Copier;

use common_Exception;
use core_kernel_classes_Resource;
use oat\tao\model\resources\Contract\InstanceContentCopierInterface;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;

class AssetContentCopier implements InstanceContentCopierInterface
{
    /** @var SharedStimulusResourceSpecification */
    private $sharedStimulusSpecification;

    /** @var CommandFactory */
    private $commandFactory;

    /** @var CopyService */
    private $sharedStimulusCopyService;

    /** @var MediaSource|null */
    private $mediaSource;

    /** @var string */
    private $defaultLanguage;

    private MediaService $mediaService;
    private FileManagement $fileManagement;

    public function __construct(
        SharedStimulusResourceSpecification $sharedStimulusResourceSpecification,
        CommandFactory $commandFactory,
        CopyService $sharedStimulusCopyService,
        MediaService $mediaService,
        FileManagement $fileManagement,
        string $defaultLanguage,
        MediaSource $mediaSource = null,
    ) {
        $this->sharedStimulusSpecification = $sharedStimulusResourceSpecification;
        $this->commandFactory = $commandFactory;
        $this->sharedStimulusCopyService = $sharedStimulusCopyService;
        $this->mediaService = $mediaService;
        $this->fileManagement = $fileManagement;
        $this->defaultLanguage = $defaultLanguage;
        $this->mediaSource = $mediaSource;
    }

    /**
     * @throws common_Exception
     */
    public function copy(
        core_kernel_classes_Resource $instance,
        core_kernel_classes_Resource $destinationInstance
    ): void {
        if ($this->sharedStimulusSpecification->isSatisfiedBy($instance)) {
            $this->sharedStimulusCopyService->copy(
                $this->commandFactory->makeCopyCommand(
                    $instance->getUri(),
                    $destinationInstance->getUri(),
                    $this->getResourceLanguageCode($instance)
                )
            );

            return;
        }

        $this->cloneAsset($instance, $destinationInstance);
    }

    /**
     * @throws common_Exception
     */
    private function cloneAsset(core_kernel_classes_Resource $fromAsset, core_kernel_classes_Resource $toAsset): void
    {
        $mediaSource = $this->mediaSource ?? new MediaSource([]);
        $fileInfo = $mediaSource->getFileInfo($fromAsset->getUri());
        $stream = $this->fileManagement->getFileStream($fileInfo['link']);
        $tmpMediaPath = tempnam(sys_get_temp_dir(), 'taoMediaManager_') . '_' . $fileInfo['name'];
        $logPrefix = sprintf(
            '[link="%s",fromLabel=%s,fromUri=%s,toLabel=%s,toUri=%s]',
            $fileInfo['link'],
            $fromAsset->getLabel(),
            $fromAsset->getUri(),
            $toAsset->getLabel(),
            $toAsset->getUri()
        );

        if (!file_put_contents($tmpMediaPath, $stream->getContents())) {
            throw new common_Exception(
                sprintf(
                    '%s Failed saving asset to a temporary file "%s"',
                    $logPrefix,
                    $tmpMediaPath,
                )
            );
        }

        $toAsset->setPropertiesValues(
            [
                TaoMediaOntology::PROPERTY_LINK => '',
            ]
        );

        if (!$this->mediaService->editMediaInstance($tmpMediaPath, $toAsset->getUri())) {
            throw new common_Exception(
                sprintf(
                    '%s Failed saving asset into filesystem while copying it',
                    $logPrefix,
                )
            );
        }

        if (is_readable($tmpMediaPath)) {
            unlink($tmpMediaPath);
        }
    }

    private function getResourceLanguageCode(
        core_kernel_classes_Resource $instance
    ): string {
        $lang = $instance->getPropertyValues(
            $instance->getProperty(TaoMediaOntology::PROPERTY_LANGUAGE)
        );

        if (empty($lang)) {
            return $this->defaultLanguage;
        }

        $langCode = trim(current($lang));

        return empty($langCode)
            ? $this->defaultLanguage
            : $langCode;
    }
}
