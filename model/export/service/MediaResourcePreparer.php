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

namespace oat\taoMediaManager\model\export\service;

use core_kernel_classes_Resource;
use Exception;
use LogicException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\media\MediaAsset;
use oat\tao\model\media\TaoMediaException;
use oat\tao\model\media\TaoMediaResolver;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use Psr\Http\Message\StreamInterface;
use qtism\data\content\BodyElement;
use qtism\data\content\xhtml\Img;
use qtism\data\content\xhtml\QtiObject;
use qtism\data\storage\xml\XmlDocument;
use tao_models_classes_FileNotFoundException;

class MediaResourcePreparer extends ConfigurableService implements MediaResourcePreparerInterface
{
    use OntologyAwareTrait;

    private const PROCESS_XML_ELEMENTS = [
        'img',
        'object',
    ];

    /** @var TaoMediaResolver */
    private $mediaResolver;

    public function prepare(core_kernel_classes_Resource $mediaResource, StreamInterface $contents): string
    {
        if (!$this->getSharedStimulusResourceSpecification()->isSatisfiedBy($mediaResource)) {
            return (string)$contents;
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadFromString((string)$contents);

        $missingMediaAssets = [];
        foreach ($this->getComponents($xmlDocument) as $component) {
            $mediaAsset = $this->getMediaAsset($component);

            if ($mediaAsset) {
                try {
                    $this->replaceComponentPath($component, $mediaAsset);
                } catch (tao_models_classes_FileNotFoundException $fileNotFoundException) {
                    $missingAsset = 'FilePath: ' . $fileNotFoundException->getFilePath();
                    if ($component instanceof Img) {
                        $missingAsset = 'Image: ' . $component->getAlt() . ' ' . $missingAsset;
                    }

                    $missingMediaAssets[] = $missingAsset;
                }
            }
        }

        if (!empty($missingMediaAssets)) {
            // Report missing file errors to upper levels in aggregated form.
            throw new MediaReferencesNotFoundException($missingMediaAssets);
        }

        return $xmlDocument->saveToString();
    }

    private function replaceComponentPath(BodyElement $component, MediaAsset $mediaAsset): void
    {
        $mediaSource = $mediaAsset->getMediaSource();

        $fileInfo = $mediaSource->getFileInfo($mediaAsset->getMediaIdentifier());

        $stream = $this->getFileManagement()->getFileStream($fileInfo['link']);

        $contents = $stream->getContents();

        if (!$contents) {
            throw new tao_models_classes_FileNotFoundException($fileInfo['link']);
        }

        $base64Content = $this->getEncodedSource($fileInfo['mime'], $contents);

        $this->setComponentSource($component, $base64Content);
    }

    public function withMediaResolver(TaoMediaResolver $mediaResolver): MediaResourcePreparerInterface
    {
        $this->mediaResolver = $mediaResolver;

        return $this;
    }

    private function getMediaResolver(): TaoMediaResolver
    {
        if (!$this->mediaResolver) {
            return $this->mediaResolver = new TaoMediaResolver();
        }

        return $this->mediaResolver;
    }

    /**
     * @return BodyElement[]
     */
    private function getComponents(XmlDocument $xmlDocument): array
    {
        $components = [];

        foreach (self::PROCESS_XML_ELEMENTS as $element) {
            $components = array_merge(
                $components,
                $xmlDocument->getDocumentComponent()
                    ->getComponentsByClassName($element)
                    ->getArrayCopy()
            );
        }

        return $components;
    }

    private function getComponentSource(BodyElement $element): string
    {
        if ($element instanceof Img) {
            return $element->getSrc();
        }

        if ($element instanceof QtiObject) {
            return $element->getData();
        }

        throw new LogicException(sprintf('Body element [%s] not supported', get_class($element)));
    }

    private function setComponentSource(BodyElement $element, string $source): void
    {
        if ($element instanceof Img) {
            $element->setSrc($source);
        }

        if ($element instanceof QtiObject) {
            $element->setData($source);
        }
    }

    /**
     * @throws Exception
     */
    private function getMediaAsset(BodyElement $component): ?MediaAsset
    {
        try {
            $source = $this->getComponentSource($component);

            $mediaAsset = $this->getMediaResolver()
                ->resolve($source);

            $mediaSource = $mediaAsset->getMediaSource();

            return $mediaSource instanceof MediaSource ? $mediaAsset : null;
        } catch (TaoMediaException $exception) {
            return null;
        }
    }

    private function getEncodedSource(string $mimeType, string $content): string
    {
        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    private function getFileManagement(): FileManagement
    {
        return $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
    }

    private function getSharedStimulusResourceSpecification(): SharedStimulusResourceSpecification
    {
        return $this->getServiceLocator()->get(SharedStimulusResourceSpecification::class);
    }
}
