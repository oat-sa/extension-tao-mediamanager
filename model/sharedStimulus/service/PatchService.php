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
use InvalidArgumentException;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\PatchCommand;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\SharedStimulusImporter;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;

class PatchService extends ConfigurableService
{
    use OntologyAwareTrait;

    /**
     * @var TaoMediaResolver
     */
    private $mediaResolver;

    /**
     * @throws core_kernel_persistence_Exception
     */
    public function patch(PatchCommand $command): SharedStimulus
    {
        /** @var File $file */
        $file = $this->getServiceLocator()->get(FileReferenceSerializer::SERVICE_ID)->unserialize(
            $command->getFileReference()
        );

        $id = $command->getId();
        $userId = $command->getUserId();

        $resource = $this->getResource($id);

        $this->validateResource($resource);
        $this->validateXml($file);

        $this->getMediaService()->editMediaInstance($file, $id, null, $userId);
        $file->delete();

        return new SharedStimulus(
            $id,
            $resource->getLabel(),
            $resource->getOnePropertyValue($this->getProperty(MediaService::PROPERTY_LANGUAGE))->getUri()
        );
    }

    private function getMediaService(): MediaService
    {
        return MediaService::singleton();
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

    private function validateXml(File $file): void
    {
        try {
            SharedStimulusImporter::isValidSharedStimulus($file);
        } catch (XmlStorageException $e) {
            $this->logAlert(sprintf('Incorrect shared stimulus xml, %s', $e->getMessage()));
            throw new InvalidArgumentException('Invalid XML provided');
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadFromString($file->read(), false);

        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');

        foreach ($images as $image) {
            $source = $image->getSrc();
            if (false === strpos($source, 'data:image')) {
                $asset = $this->getMediaResolver()->resolve($source);
                if ($asset->getMediaSource() instanceof MediaSource) {
                    $info = $asset->getMediaSource()->getFileInfo($asset->getMediaIdentifier());
                }
            }
        }
    }

    public function withMediaResolver(TaoMediaResolver $resolver): self
    {
        $this->mediaResolver = $resolver;
        return $this;
    }

    private function getMediaResolver(): TaoMediaResolver
    {
        return $this->mediaResolver ?? new TaoMediaResolver();
    }
}
