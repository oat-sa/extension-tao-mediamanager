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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoMediaManager\model\sharedStimulus;

use common_Exception;
use common_exception_Error;
use core_kernel_classes_Class;
use ErrorException;
use FileNotFoundException;
use oat\tao\model\upload\UploadService;
use oat\taoMediaManager\model\SharedStimulusImporter;

class CreateSharedStimulusService
{
    /** @var UploadService */
    private $uploadService;

    /** @var SharedStimulusImporter */
    private $sharedStimulusImporter;

    /** @var core_kernel_classes_Class */
    private $kernelClass;

    /** @var string */
    private $sharedStimulusTemplatePath;

    /** @var string */
    private $tempUploadPath;

    public function __construct(
        UploadService $uploadService,
        SharedStimulusImporter $sharedStimulusImporter,
        core_kernel_classes_Class $kernelClass,
        string $sharedStimulusTemplatePath,
        string $tempUploadPath
    )
    {
        $this->uploadService = $uploadService;
        $this->sharedStimulusImporter = $sharedStimulusImporter;
        $this->sharedStimulusTemplatePath = $sharedStimulusTemplatePath;
        $this->tempUploadPath = $tempUploadPath;
        $this->kernelClass = $kernelClass;
    }

    /**
     * @param string $language
     * @param string $name
     *
     * @return SharedStimulus
     *
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws FileNotFoundException
     * @throws ErrorException
     */
    public function createEmpty(string $language, string $name): SharedStimulus
    {
        $fileName = 'shared_stimulus_' . uniqid() . '.xml';
        $filePath = $this->tempUploadPath . DIRECTORY_SEPARATOR . $fileName;

        $sharedStimulusTemplateContent = file_get_contents($this->sharedStimulusTemplatePath);

        if ($sharedStimulusTemplateContent === false) {
            throw new FileNotFoundException(
                sprintf(
                    'Shared Stimulus template %s not found',
                    $this->sharedStimulusTemplatePath
                )
            );
        }

        if (file_put_contents($filePath, $sharedStimulusTemplateContent) === false) {
            throw new ErrorException(
                sprintf(
                    'Could not save Shared Stimulus to temporary path %s',
                    $filePath
                )
            );
        }

        $uploadResponse = $this->uploadService
            ->uploadFile(
                [
                    'name' => $fileName,
                    'tmp_name' => $filePath
                ],
                DIRECTORY_SEPARATOR
            );

        $importResponse = $this->sharedStimulusImporter
            ->import(
                $this->kernelClass,
                [
                    'lang' => $language,
                    'source' => [
                        'name' => $name,
                    ],
                    'uploaded_file' => DIRECTORY_SEPARATOR
                        . $this->uploadService->getUserDirectoryHash()
                        . DIRECTORY_SEPARATOR
                        . $uploadResponse['uploaded_file']
                ]
            );

        return new SharedStimulus(current($importResponse->getChildren())->getData()['uriResource']);
    }
}
