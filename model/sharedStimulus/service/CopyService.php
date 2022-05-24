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

use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use InvalidArgumentException;
use oat\generis\model\data\Ontology;
use oat\generis\model\resource\Contract\ResourceRepositoryInterface;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\fileManagement\FlySystemManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\CopyCommand;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets as ListStylesheetsDTO;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\dto\SharedStimulusInstanceData;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\TaoMediaOntology;

/**
 * @todo Unit tests
 */
class CopyService
{
    /** @var Ontology */
    private $ontology;

    /** @var MediaService */
    private $mediaService;

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

    /**
     * @var \common_Logger
     * @todo Remove before merge
     */
    private $log;

    public function __construct(
        Ontology $ontology,
        MediaService $mediaService,
        StoreService $sharedStimulusStoreService,
        ListStylesheetsService $listStylesheetsService,
        StylesheetRepository $stylesheetRepository,
        FileSourceUnserializer $fileSourceUnserializer,
        FileManagement $fileManagement
    ) {
        $this->ontology = $ontology;
        $this->mediaService = $mediaService;
        $this->sharedStimulusStoreService = $sharedStimulusStoreService;
        $this->listStylesheetsService = $listStylesheetsService;
        $this->stylesheetRepository = $stylesheetRepository;
        $this->fileSourceUnserializer = $fileSourceUnserializer;
        $this->fileManagement = $fileManagement;

        $this->log = \common_Logger::singleton(); // @todo Remove before merge
    }

    public function copy(CopyCommand $command): SharedStimulus
    {
        $this->assertHasRequiredParameters($command);

        $source = SharedStimulusInstanceData::fromResource(
            $this->ontology->getResource($command->getSourceUri()),
            $command->getLanguage()
        );

        $this->log->logInfo(sprintf('source.language = %s', $source->language));
        $this->log->logInfo(sprintf('source.label    = %s', $source->label));
        $this->log->logInfo(sprintf('source.link     = %s', $source->link));
        $this->log->logInfo(sprintf('source.altText  = %s', $source->altText));
        $this->log->logInfo(sprintf('source.md5      = %s', $source->md5));
        $this->log->logInfo(sprintf('source.mimeType = %s', $source->mimeType));


        $target = $this->ontology->getResource($command->getDestinationUri());
        $type = $this->getTargetClass($target);

        $classUri = $type->getUri();

        $this->log->logInfo(sprintf('target.uri  = %s', $target->getUri()));
        $this->log->logInfo(sprintf('classUri    = %s', $classUri));

        // We still need to copy both the CSS and the XML

        $cssFiles = $this->listStylesheetsService->getList(
            new ListStylesheetsDTO($source->resourceUri)
        );

        $cssPath = $this->stylesheetRepository->getPath($command->getSourceUri());
        $this->log->logInfo(sprintf('cssPath  = %s', serialize($cssPath)));
        $this->log->logInfo(sprintf('cssFiles = %s', serialize($cssFiles)));

        $newCssFiles = [];
        $tempFiles = [];
        $tmpBaseDir = $this->getTempBaseDir();

        $this->registerFileCleanupCallback($tempFiles, $tmpBaseDir);

        foreach ($cssFiles as $baseName) {
            $destinationPath = $this->copyCSSToTempFile(
                $tmpBaseDir,
                $cssPath . DIRECTORY_SEPARATOR . StoreService::CSS_DIR_NAME,
                $baseName
            );

            $tempFiles[] = $destinationPath;
            $newCssFiles[] = $destinationPath;
        }

        $this->log->logInfo(sprintf('$tempFiles  = %s', serialize($tempFiles)));
        $this->log->logInfo(sprintf('$newCssFiles  = %s', serialize($newCssFiles)));

        // 2- Get XML path
        //
        $xmlSourceFile = $this->fileSourceUnserializer->unserialize($source->link);
        $this->log->logInfo(sprintf('xmlSourceFile  = %s', $xmlSourceFile));

        // 3- Call storeService->store with the css files
        //
        $dirname = $this->sharedStimulusStoreService->storeStream(
            $this->fileManagement->getFileStream($xmlSourceFile)->detach(),
            basename($source->link),
            $newCssFiles
        );

        $this->log->logInfo(sprintf('copied data to %s', $dirname));

        return new SharedStimulus(
            $source->resourceUri,
            $target->getUri(),
            $command->getLanguage()
        );
    }

    private function copyCSSToTempFile(
        string $tmpBaseDir,
        string $cssPath,
        string $baseName
    ): string {
        $data = $this->stylesheetRepository->read(
            $cssPath . DIRECTORY_SEPARATOR . $baseName
        );

        $destinationPath = $tmpBaseDir . DIRECTORY_SEPARATOR . $baseName;

        if (!file_put_contents($destinationPath, $data)) {
            throw new \Exception('shit fuck crap');
        }

        return $destinationPath;
    }

    private function getTargetClass(core_kernel_classes_Resource $resource): core_kernel_classes_Class {
        $types = $resource->getTypes();

        $this->log->logInfo(sprintf('types.count  = %d', count($types)));
        if (count($types) != 1) {
            throw new \Exception('getTypes() returned an unexpected number of types');
        }

        /** @var $type core_kernel_classes_Class */
        $type = current($types);
        if (!isset($type)) {
            throw new \Exception('Unable to retrieve target class type');
        }

        $this->log->logInfo(sprintf('type  = %s', $type->getUri()));

        return $type;

        //$classUri = $target->getTypes()[0]->getUri();
        /*$classUri = $type->getUri();

        $this->log->logInfo(sprintf('target.uri  = %s', $target->getUri()));*/
    }

    private function assertHasRequiredParameters(CopyCommand $command): void
    {
        if ('' === trim($command->getSourceUri())
            || '' === trim($command->getDestinationUri())
            || '' === trim($command->getLanguage()) ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument of type %s is missing a required parameter',
                    CopyCommand::class
                )
            );
        }
    }

    private function registerFileCleanupCallback(
        array &$tempFiles,
        ?string $tmpBaseDir
    ): void
    {
        register_shutdown_function(function () use (&$tempFiles, $tmpBaseDir)
        {
            foreach ($tempFiles as $file) {
                @ unlink($file);
            }

            @ unlink($tmpBaseDir);
        });
    }

    private function getTempBaseDir(): string
    {
        $tmpBaseDir = tempnam(sys_get_temp_dir(), 'MediaManagerCopy');
        @ unlink($tmpBaseDir);
        mkdir ($tmpBaseDir);

        return $tmpBaseDir;
    }


}
