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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2022-2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use oat\generis\model\data\Ontology;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\filesystem\FilesystemInterface;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\sharedStimulus\CopyCommand;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets as ListStylesheetsDTO;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\dto\SharedStimulusInstanceData;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use InvalidArgumentException;
use oat\taoMediaManager\model\TaoMediaOntology;

class CopyService
{
    /** @var Ontology */
    private $ontology;

    /** @var StoreService */
    private $sharedStimulusStoreService;

    /** @var ListStylesheetsService */
    private $listStylesheetsService;

    /** @var StylesheetRepository */
    private $stylesheetRepository;

    /** @var FileSourceUnserializer */
    private $fileSourceUnserializer;

    /** @var FileManagement */
    private $fileManagement;

    public function __construct(
        Ontology $ontology,
        StoreService $sharedStimulusStoreService,
        ListStylesheetsService $listStylesheetsService,
        StylesheetRepository $stylesheetRepository,
        FileSourceUnserializer $fileSourceUnserializer,
        FileManagement $fileManagement
    ) {
        $this->ontology = $ontology;
        $this->sharedStimulusStoreService = $sharedStimulusStoreService;
        $this->listStylesheetsService = $listStylesheetsService;
        $this->stylesheetRepository = $stylesheetRepository;
        $this->fileSourceUnserializer = $fileSourceUnserializer;
        $this->fileManagement = $fileManagement;
    }

    public function copy(CopyCommand $command): SharedStimulus
    {
        $this->assertHasRequiredParameters($command);

        $source = SharedStimulusInstanceData::fromResource(
            $this->ontology->getResource($command->getSourceUri()),
            $command->getLanguage()
        );

        $srcXmlPath = $this->fileSourceUnserializer->unserialize($source->link);
        $stimulusFilename = basename($source->link);

        $dirname = $this->sharedStimulusStoreService->getUniqueDirName($stimulusFilename);

        $this->sharedStimulusStoreService->storeXmlStream(
            $this->fileManagement->getFileStream($srcXmlPath)->detach(),
            $stimulusFilename,
            $dirname
        );

        $this->copyCSSFilesDirectly($source, $dirname);

        $target = $this->ontology->getResource($command->getDestinationUri());

        $target->setPropertyValue(
            $target->getProperty(TaoMediaOntology::PROPERTY_LINK),
            $dirname . DIRECTORY_SEPARATOR . $stimulusFilename
        );

        return new SharedStimulus(
            $source->resourceUri,
            $target->getUri(),
            $command->getLanguage()
        );
    }

    private function copyCSSFilesDirectly(SharedStimulusInstanceData $source, string $destinationDir): void
    {
        $cssPath = $this->stylesheetRepository->getPath($source->resourceUri);
        $cssFiles = $this->listStylesheetsService->getList(
            new ListStylesheetsDTO($source->resourceUri)
        );

        if (empty($cssFiles['children'])) {
            return;
        }

        $fs = $this->getFileSystem();

        $destCssDir = $destinationDir . DIRECTORY_SEPARATOR . StoreService::CSS_DIR_NAME;

        $fs->createDirectory($destCssDir);

        foreach ($cssFiles['children'] as $child) {
            $sourcePath = $cssPath . DIRECTORY_SEPARATOR . StoreService::CSS_DIR_NAME . DIRECTORY_SEPARATOR . $child['name'];
            $destPath = $destCssDir . DIRECTORY_SEPARATOR . $child['name'];

            if (!$fs->fileExists($sourcePath)) {
                continue;
            }

            $sourceStream = $fs->readStream($sourcePath);

            if (!is_resource($sourceStream)) {
                continue;
            }

            try {
                $fs->writeStream($destPath, $sourceStream);
            } finally {
                if (is_resource($sourceStream)) {
                    fclose($sourceStream);
                }
            }
        }
    }

    /**
     * Get the filesystem for direct file operations
     *
     * @return FilesystemInterface
     */
    private function getFileSystem(): FilesystemInterface
    {
        $flySystemManagement = $this->sharedStimulusStoreService
            ->getServiceLocator()
            ->get(FlySystemManagement::SERVICE_ID);

        return $this->sharedStimulusStoreService
            ->getServiceLocator()
            ->get(FileSystemService::SERVICE_ID)
            ->getFileSystem($flySystemManagement->getOption(FlySystemManagement::OPTION_FS));
    }

    private function assertHasRequiredParameters(CopyCommand $command): void
    {
        if (
            '' === trim($command->getSourceUri())
            || '' === trim($command->getDestinationUri())
            || '' === trim($command->getLanguage())
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument of type %s is missing a required parameter',
                    CopyCommand::class
                )
            );
        }
    }
}
