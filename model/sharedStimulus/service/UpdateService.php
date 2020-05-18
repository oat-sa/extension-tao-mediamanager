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

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use core_kernel_classes_Resource as Resource;
use core_kernel_persistence_Exception;
use ErrorException;
use InvalidArgumentException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\sharedStimulus\UpdateCommand;
use oat\taoMediaManager\model\SharedStimulusImporter;
use qtism\data\storage\xml\XmlDocument;

class UpdateService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const OPTION_TEMP_UPLOAD_PATH = 'temp_upload_path';

    /**
     * @throws ErrorException
     * @throws core_kernel_persistence_Exception
     */
    public function update(UpdateCommand $command): SharedStimulus
    {
        $newBody = $command->getBody();
        $id = $command->getId();
        $userId = $command->getUserId();

        $resource = $this->getResource($id);

        $fileName = $this->getTempFileName();
        $filePath = $this->saveTemporaryFile($fileName, $newBody);

        $this->validateResource($resource);
        $this->validateXml($filePath);

        $this->getMediaService()->editMediaInstance($filePath, $id, null, $userId);

        return new SharedStimulus(
            $id,
            $resource->getLabel(),
            (string)$resource->getOnePropertyValue($this->getProperty(MediaService::PROPERTY_LANGUAGE)),
            $newBody
        );
    }


    private function getTempFileName(): string
    {
        return 'shared_stimulus_' . uniqid() . '.xml';
    }

    private function getMediaService(): MediaService
    {
        return MediaService::singleton();
    }

    /**
     * @throws ErrorException
     */
    private function saveTemporaryFile(string $fileName, string $templateContent): string
    {
        $fileDirectory = $this->getOption(self::OPTION_TEMP_UPLOAD_PATH) ?? sys_get_temp_dir();

        $filePath = $fileDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!is_writable($fileDirectory) || file_put_contents($filePath, $templateContent) === false) {
            throw new ErrorException(
                sprintf(
                    'Could not save Shared Stimulus to temporary path %s',
                    $filePath
                )
            );
        }

        return $filePath;
    }

    /**
     * @param Resource $resource
     */
    private function validateResource(Resource $resource): void
    {
        if (!$resource->isInstanceOf($this->getClass(MediaService::ROOT_CLASS_URI))) {
            $this->logAlert(
                sprintf(
                    'Incorrect resource provided, %s should be subtype of  %s',
                    $resource->getUri(),
                    MediaService::ROOT_CLASS_URI
                )
            );
            throw new InvalidArgumentException('Invalid resource provided');
        }
    }

    private function validateXml(string $fileName): void
    {
        SharedStimulusImporter::isValidSharedStimulus($fileName);

        $resolver = new TaoMediaResolver();
        $xmlDocument = new XmlDocument();
        $xmlDocument->load($fileName, false);

        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');

        foreach ($images as $image) {
            $source = $image->getSrc();
            if (false === strpos($source, 'data:image')) {
                $asset = $resolver->resolve($source);
                if ($asset instanceof MediaAsset) {
                    $info = $asset->getMediaSource()->getFileInfo($asset->getMediaIdentifier());
                }
            }
        }
    }
}
