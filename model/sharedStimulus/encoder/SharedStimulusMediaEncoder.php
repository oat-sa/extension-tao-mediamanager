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

namespace oat\taoMediaManager\model\sharedStimulus\encoder;

use common_exception_Error;
use helpers_File;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\import\InvalidSourcePathException;
use qtism\data\content\xhtml\Img;
use qtism\data\content\xhtml\ObjectElement;
use qtism\data\storage\xml\marshalling\MarshallingException;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlStorageException;
use tao_helpers_File;
use tao_models_classes_FileNotFoundException;

class SharedStimulusMediaEncoder extends ConfigurableService implements SharedStimulusMediaEncoderInterface
{
    /**
     * @throws common_exception_Error
     * @throws MarshallingException
     * @throws XmlStorageException
     * @throws tao_models_classes_FileNotFoundException
     * @throws InvalidSourcePathException
     */
    public function encodeAssets(string $passageXmlFilePath): string
    {
        $baseDir = dirname($passageXmlFilePath) . DIRECTORY_SEPARATOR;

        $xmlDocument = new XmlDocument();
        $xmlDocument->load($passageXmlFilePath, true);

        $images = $xmlDocument->getDocumentComponent()->getComponentsByClassName('img');
        $objects = $xmlDocument->getDocumentComponent()->getComponentsByClassName('object');

        /** @var $image Img */
        foreach ($images as $image) {
            $source = $image->getSrc();
            $this->validateSource($baseDir, $source);
            $image->setSrc($this->secureEncode($baseDir, $source));
        }

        /** @var $object ObjectElement */
        foreach ($objects as $object) {
            $data = $object->getData();
            $this->validateSource($baseDir, $data);
            $object->setData($this->secureEncode($baseDir, $data));
        }

        // save the document to a tempfile
        $newPassageXmlFilePath = tempnam(sys_get_temp_dir(), 'sharedStimulus_') . '.xml';
        $xmlDocument->save($newPassageXmlFilePath);

        return $newPassageXmlFilePath;
    }

    /**
     * @throws InvalidSourcePathException
     */
    protected function validateSource(string $baseDir, string $sourcePath): void
    {
        $urlData = parse_url($sourcePath);

        if (!empty($urlData['scheme'])) {
            return;
        }

        if (!helpers_File::isFileInsideDirectory($sourcePath, $baseDir)) {
            throw new InvalidSourcePathException($baseDir, $sourcePath);
        }
    }

    /**
     * Verify paths and encode the file
     *
     * @throws tao_models_classes_FileNotFoundException
     * @throws common_exception_Error
     */
    protected function secureEncode(string $basedir, string $source): string
    {
        $components = parse_url($source);

        if (!isset($components['scheme'])) {
            if (tao_helpers_File::securityCheck($source, false)) {
                if (file_exists($basedir . $source)) {
                    return 'data:' . tao_helpers_File::getMimeType($basedir . $source) . ';'
                        . 'base64,' . base64_encode(file_get_contents($basedir . $source));
                }

                throw new tao_models_classes_FileNotFoundException($source);
            }

            throw new common_exception_Error('Invalid source path "' . $source . '"');
        }

        return $source;
    }
}