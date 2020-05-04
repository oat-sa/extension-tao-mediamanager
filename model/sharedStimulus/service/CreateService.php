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
use core_kernel_classes_Class;
use ErrorException;
use FileNotFoundException;
use oat\generis\model\data\Ontology;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\upload\UploadService;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\SharedStimulusImporter;

class CreateService extends ConfigurableService
{
    public const DEFAULT_NAME = 'passage NEW';
    public const OPTION_TEMP_UPLOAD_PATH = 'temp_upload_path';
    public const OPTION_TEMPLATE_PATH = 'template_path';

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

        $uploadService = $this->getUploadService();

        $uploadResponse = $uploadService
            ->uploadFile(
                [
                    'name' => $fileName,
                    'tmp_name' => $filePath
                ],
                DIRECTORY_SEPARATOR
            );

        $kernelClass = $this->getOntology()->getClass($command->getClassUri());

        $sharedStimulusName = $this->getSharedStimulusName($command, $kernelClass);

        $importResponse = $this->getSharedStimulusImporter()
            ->import(
                $kernelClass,
                [
                    'lang' => $command->getLanguageUri(),
                    'source' => [
                        'name' => $sharedStimulusName,
                        'type' => 'application/qti+xml',
                    ],
                    'uploaded_file' => DIRECTORY_SEPARATOR
                        . $uploadService->getUserDirectoryHash()
                        . DIRECTORY_SEPARATOR
                        . $uploadResponse['uploaded_file']
                ]
            );

        return new SharedStimulus(
            current($importResponse->getChildren())->getData()['uriResource'],
            $sharedStimulusName,
            $command->getLanguageUri()
        );
    }

    private function getSharedStimulusName(CreateCommand $command, core_kernel_classes_Class $kernelClass): string
    {
        if ($command->getName()) {
            return $command->getName();
        }

        $totalInstances = count($kernelClass->getInstances());

        return $totalInstances === 0 ? self::DEFAULT_NAME : (self::DEFAULT_NAME . ' ' . $totalInstances);
    }

    private function getTempFileName(): string
    {
        return 'shared_stimulus_' . uniqid() . '.xml';
    }

    private function getOntology(): Ontology
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID);
    }

    private function getUploadService(): UploadService
    {
        return $this->getServiceLocator()->get(UploadService::SERVICE_ID);
    }

    private function getSharedStimulusImporter(): SharedStimulusImporter
    {
        return $this->getServiceLocator()->get(SharedStimulusImporter::class);
    }

    private function getTempFilePath(string $fileName): string
    {
        return $this->getTempUploadPath() . DIRECTORY_SEPARATOR . $fileName;
    }

    private function getTempUploadPath(): string
    {
        return $this->getOption(self::OPTION_TEMP_UPLOAD_PATH) ?? sys_get_temp_dir();
    }

    private function getTemplateFilePath(): string
    {
        return $this->getOption(self::OPTION_TEMPLATE_PATH) ?? __DIR__
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
            . 'views'
            . DIRECTORY_SEPARATOR
            . 'templates'
            . DIRECTORY_SEPARATOR
            . 'sharedStimulus'
            . DIRECTORY_SEPARATOR
            . 'empty_template.xml';
    }

    /**
     * @throws FileNotFoundException
     */
    private function getDefaultTemplateContent(): string
    {
        $templatePath = $this->getTemplateFilePath();
        $templateContent = file_get_contents($templatePath);

        if ($templateContent === false) {
            throw new FileNotFoundException(sprintf('Shared Stimulus template %s not found', $templatePath));
        }

        return $templateContent;
    }

    /**
     * @throws ErrorException
     */
    private function saveTemporaryFile(string $filePath, string $templateContent): void
    {
        if (file_put_contents($filePath, $templateContent) === false) {
            throw new ErrorException(
                sprintf(
                    'Could not save Shared Stimulus to temporary path %s',
                    $filePath
                )
            );
        }
    }
}
