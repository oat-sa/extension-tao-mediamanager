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

namespace oat\taoMediaManager\model\sharedStimulus\service;

use common_Exception;
use common_exception_Error;
use ErrorException;
use FileNotFoundException;
use oat\generis\model\data\Ontology;
use oat\tao\model\upload\UploadService;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\SharedStimulusImporter;

class CreateService
{
    /** @var UploadService */
    private $uploadService;

    /** @var SharedStimulusImporter */
    private $sharedStimulusImporter;

    /** @var Ontology */
    private $ontology;

    /** @var string */
    private $sharedStimulusTemplatePath;

    /** @var string */
    private $tempUploadPath;

    public function __construct(
        UploadService $uploadService,
        SharedStimulusImporter $sharedStimulusImporter,
        Ontology $ontology,
        string $sharedStimulusTemplatePath = null,
        string $tempUploadPath = null
    )
    {
        $this->uploadService = $uploadService;
        $this->sharedStimulusImporter = $sharedStimulusImporter;
        $this->sharedStimulusTemplatePath = $sharedStimulusTemplatePath;
        $this->tempUploadPath = $tempUploadPath;

        if ($this->sharedStimulusTemplatePath === null) {
            $this->sharedStimulusTemplatePath = __DIR__
                . DIRECTORY_SEPARATOR
                . '..'
                . DIRECTORY_SEPARATOR
                . '..'
                . DIRECTORY_SEPARATOR
                . '..'
                . DIRECTORY_SEPARATOR
                . 'assets'
                . DIRECTORY_SEPARATOR
                . 'sharedStimulus'
                . DIRECTORY_SEPARATOR
                . 'empty_template.xml';
        }

        if ($this->tempUploadPath === null) {
            $this->tempUploadPath = sys_get_temp_dir();
        }
        $this->ontology = $ontology;
    }

    /**
     * @param CreateCommand $command
     *
     * @return SharedStimulus
     *
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws FileNotFoundException
     * @throws ErrorException
     */
    public function create(CreateCommand $command): SharedStimulus
    {
        $fileName = $this->getTempFileName();
        $filePath = $this->getTempFilePath($fileName);

        $this->saveTemporaryFile($filePath, $this->getDefaultTemplateContent());

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
                $this->ontology->getClass($command->getClassUri()),
                [
                    'lang' => $command->getLanguageUri(),
                    'source' => [
                        'name' => $command->getName(),
                    ],
                    'uploaded_file' => DIRECTORY_SEPARATOR
                        . $this->uploadService->getUserDirectoryHash()
                        . DIRECTORY_SEPARATOR
                        . $uploadResponse['uploaded_file']
                ]
            );

        return new SharedStimulus(
            current($importResponse->getChildren())->getData()['uriResource'],
            $command->getName(),
            $command->getLanguageUri()
        );
    }

    private function getTempFileName(): string
    {
        return 'shared_stimulus_' . uniqid() . '.xml';
    }

    private function getTempFilePath(string $fileName): string
    {
        return $this->tempUploadPath . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getDefaultTemplateContent(): string
    {
        $sharedStimulusTemplateContent = file_get_contents($this->sharedStimulusTemplatePath);

        if ($sharedStimulusTemplateContent === false) {
            throw new FileNotFoundException(
                sprintf(
                    'Shared Stimulus template %s not found',
                    $this->sharedStimulusTemplatePath
                )
            );
        }

        return $sharedStimulusTemplateContent;
    }

    /**
     * @throws ErrorException
     */
    private function saveTemporaryFile(string $filePath, string $sharedStimulusTemplateContent): void
    {
        if (file_put_contents($filePath, $sharedStimulusTemplateContent) === false) {
            throw new ErrorException(
                sprintf(
                    'Could not save Shared Stimulus to temporary path %s',
                    $filePath
                )
            );
        }
    }
}
