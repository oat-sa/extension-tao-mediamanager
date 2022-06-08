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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use oat\generis\model\data\Ontology;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\sharedStimulus\CopyCommand;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets as ListStylesheetsDTO;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\dto\SharedStimulusInstanceData;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use InvalidArgumentException;

class CopyService
{
    private const NAMESPACE_TEMP_FILES = 'MediaManagerCopyService';

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

    /** @var TempFileWriter */
    private $tempFileWriter;

    public function __construct(
        Ontology $ontology,
        StoreService $sharedStimulusStoreService,
        ListStylesheetsService $listStylesheetsService,
        StylesheetRepository $stylesheetRepository,
        FileSourceUnserializer $fileSourceUnserializer,
        FileManagement $fileManagement,
        TempFileWriter $tempFileWriter
    ) {
        $this->ontology = $ontology;
        $this->sharedStimulusStoreService = $sharedStimulusStoreService;
        $this->listStylesheetsService = $listStylesheetsService;
        $this->stylesheetRepository = $stylesheetRepository;
        $this->fileSourceUnserializer = $fileSourceUnserializer;
        $this->fileManagement = $fileManagement;
        $this->tempFileWriter = $tempFileWriter;
    }

    public function copy(CopyCommand $command): SharedStimulus
    {
        $this->assertHasRequiredParameters($command);

        $source = SharedStimulusInstanceData::fromResource(
            $this->ontology->getResource($command->getSourceUri()),
            $command->getLanguage()
        );

        $srcXmlPath = $this->fileSourceUnserializer->unserialize($source->link);

        $this->sharedStimulusStoreService->storeStream(
            $this->fileManagement->getFileStream($srcXmlPath)->detach(),
            basename($source->link),
            $this->copyCSSFilesFrom($source)
        );

        $target = $this->ontology->getResource($command->getDestinationUri());

        return new SharedStimulus(
            $source->resourceUri,
            $target->getUri(),
            $command->getLanguage()
        );
    }

    private function copyCSSFilesFrom(SharedStimulusInstanceData $source): array
    {
        $cssPath = $this->stylesheetRepository->getPath($source->resourceUri);
        $cssFiles = $this->listStylesheetsService->getList(
            new ListStylesheetsDTO($source->resourceUri)
        );

        $newCssFiles = [];

        foreach ($cssFiles as $baseName) {
            $newCssFiles[] = $this->tempFileWriter->writeFile(
                self::NAMESPACE_TEMP_FILES,
                $baseName,
                $this->stylesheetRepository->read(
                    implode(
                        DIRECTORY_SEPARATOR,
                        [$cssPath , StoreService::CSS_DIR_NAME, $baseName]
                    )
                )
            );
        }

        return $newCssFiles;
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
