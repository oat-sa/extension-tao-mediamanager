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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use core_kernel_classes_ContainerCollection;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\model\OntologyRdfs;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\sharedStimulus\CopyCommand;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\service\TempFileWriter;
use oat\taoMediaManager\model\TaoMediaOntology;
use PHPUnit\Framework\MockObject\MockObject;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class CopyServiceTest extends TestCase
{
    /** @var Ontology|MockObject */
    private $ontology;

    /** @var StoreService|MockObject */
    private $storeService;

    /** @var ListStylesheetsService|MockObject */
    private $listStylesheetsService;

    /** @var StylesheetRepository|MockObject */
    private $stylesheetRepository;

    /** @var FileSourceUnserializer|MockObject */
    private $fileSourceUnserializer;

    /** @var FileManagement|MockObject */
    private $fileManagement;

    /** @var TempFileWriter|MockObject */
    private $tempFileWriter;

    /** @var CopyCommand|MockObject */
    private $copyCommand;

    /** @var CopyService */
    private $sut;

    public function setUp(): void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->storeService = $this->createMock(StoreService::class);
        $this->listStylesheetsService = $this->createMock(
            ListStylesheetsService::class
        );
        $this->stylesheetRepository = $this->createMock(
            StylesheetRepository::class
        );
        $this->fileSourceUnserializer = $this->createMock(
            FileSourceUnserializer::class
        );
        $this->fileManagement = $this->createMock(FileManagement::class);
        $this->tempFileWriter = $this->createMock(TempFileWriter::class);
        $this->copyCommand = $this->createMock(CopyCommand::class);

        $this->sut = new CopyService(
            $this->ontology,
            $this->storeService,
            $this->listStylesheetsService,
            $this->stylesheetRepository,
            $this->fileSourceUnserializer,
            $this->fileManagement,
            $this->tempFileWriter
        );
    }

    /**
     * @dataProvider missingRequiredCommandParametersDataProvider
     */
    public function testMissingRequiredCommandParameters(
        string $sourceUri,
        string $destinationUri,
        string $language
    ): void {
        $this->copyCommand
            ->method('getSourceUri')
            ->willReturn($sourceUri);

        $this->copyCommand
            ->method('getDestinationUri')
            ->willReturn($destinationUri);

        $this->copyCommand
            ->method('getLanguage')
            ->willReturn($language);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument of type %s is missing a required parameter',
                CopyCommand::class
            )
        );

        $this->sut->copy($this->copyCommand);
    }

    public function missingRequiredCommandParametersDataProvider(): array
    {
        return [
            'Empty source URI' => [
                'sourceUri' => '',
                'destinationUri' => 'http://example.com/resource2',
                'language' => 'http://example.com/languageId',
            ],
            'Empty destination URI' => [
                'sourceUri' => 'http://example.com/resource1',
                'destinationUri' => '',
                'language' => 'http://example.com/languageId',
            ],
            'Empty language' => [
                'sourceUri' => 'http://example.com/resource1',
                'destinationUri' => 'http://example.com/resource2',
                'language' => '',
            ],
        ];
    }

    public function testSuccessfulCopy(): void
    {
        $this->copyCommand
            ->method('getSourceUri')
            ->willReturn('http://example.com/resource1');

        $this->copyCommand
            ->method('getDestinationUri')
            ->willReturn('http://example.com/resource2');

        $this->copyCommand
            ->method('getLanguage')
            ->willReturn('http://example.com/languageId');

        $sourceResource = $this->getSrcResourceMock(6);
        $sourceResource
            ->expects($this->once())
            ->method('getUri')
            ->willReturn('http://example.com/resource1');

        $targetResource = $this->createMock(core_kernel_classes_Resource::class);
        $targetResource
            ->expects($this->once())
            ->method('getUri')
            ->willReturn('http://example.com/resource2');

        $propertyMock = $this->createMock(core_kernel_classes_Property::class);
        $targetResource
            ->expects($this->once())
            ->method('getProperty')
            ->willReturn($propertyMock);

        $this->ontology
            ->expects($this->at(0))
            ->method('getResource')
            ->with('http://example.com/resource1')
            ->willReturn($sourceResource);

        $this->ontology
            ->expects($this->at(1))
            ->method('getResource')
            ->with('http://example.com/resource2')
            ->willReturn($targetResource);

        $this->fileSourceUnserializer
            ->expects($this->once())
            ->method('unserialize')
            ->with('dataPath/theLink')
            ->willReturn('unit://srcXmlPath');

        $fileStreamMock = $this->createMock(StreamInterface::class);
        $fileStreamMock
            ->expects($this->once())
            ->method('detach')
            ->willReturn('theStreamResource');

        $this->fileManagement
            ->expects($this->once())
            ->method('getFileStream')
            ->with('unit://srcXmlPath')
            ->willReturn($fileStreamMock);

        $this->stylesheetRepository
            ->expects($this->once())
            ->method('getPath')
            ->with('http://example.com/resource1')
            ->willReturn('css://path');

        $path = function ($baseName): string {
            return implode(
                DIRECTORY_SEPARATOR,
                ['css://path' , StoreService::CSS_DIR_NAME, $baseName]
            );
        };

        $this->stylesheetRepository
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnMap([
                [$path('cssBasename1'), 'cssData1'],
                [$path('cssBasename2'), 'cssData2'],
            ]);

        $this->listStylesheetsService
            ->expects($this->once())
            ->method('getList')
            ->willReturnCallback(function (ListStylesheets $dto) {
                if ($dto->getUri() !== 'http://example.com/resource1') {
                    $this->fail(
                        "Unexpected call to getList for URI {$dto->getUri()}"
                    );
                }
            })
            ->willReturn([
                'path' => DIRECTORY_SEPARATOR,
                'label' => 'Passage stylesheets',
                'childrenLimit' => 100,
                'total' => 2,
                'children' => [
                    [
                        'name' => 'cssBasename1',
                        'uri' => DIRECTORY_SEPARATOR . 'cssBasename1',
                        'mime' => 'text/css',
                        'filePath' => DIRECTORY_SEPARATOR . 'cssBasename1',
                        'size' => 100,
                    ],
                    [
                        'name' => 'cssBasename2',
                        'uri' => DIRECTORY_SEPARATOR . 'cssBasename2',
                        'mime' => 'text/css',
                        'filePath' => DIRECTORY_SEPARATOR . 'cssBasename2',
                        'size' => 200,
                    ],
                ],
            ]);

        $this->tempFileWriter
            ->expects($this->exactly(2))
            ->method('writeFile')
            ->willReturnCallback(function (string $namespace, string $basename, string $data) {
                if ($namespace !== 'MediaManagerCopyService') {
                    $this->fail('Expecting namespace to be MediaManagerCopyService');
                }

                switch ($basename) {
                    case 'cssBasename1':
                        $this->assertEquals(
                            'cssData1',
                            $data,
                            'Unexpected CSS data provided for writeFile call (#1)'
                        );
                        break;

                    case 'cssBasename2':
                        $this->assertEquals(
                            'cssData2',
                            $data,
                            'Unexpected CSS data provided for writeFile call (#2)'
                        );
                        break;

                    default:
                        $this->fail(
                            "Requested a file write for an unexpected basename {$basename}"
                        );
                }

                return "css://writtenFile/{$basename}";
            });

        $newCssFiles = [
            'css://writtenFile/cssBasename1',
            'css://writtenFile/cssBasename2',
        ];

        $this->storeService
            ->expects($this->once())
            ->method('storeStream')
            ->with('theStreamResource', 'theLink', $newCssFiles);

        $stimulus = $this->sut->copy($this->copyCommand);
        $data = $stimulus->jsonSerialize();

        $this->assertEquals('http://example.com/resource1', $stimulus->getId());
        $this->assertEquals('http://example.com/resource2', $data['name']);
        $this->assertEquals('http://example.com/languageId', $data['languageId']);
    }

    private function getSrcResourceMock(int $times): MockObject
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);

        $resource
            ->expects($this->exactly($times))
            ->method('getPropertyValuesByLg')
            ->willReturnCallback(function (
                core_kernel_classes_Property $p,
                $lang
            ): core_kernel_classes_ContainerCollection {
                $values = [
                    OntologyRdfs::RDFS_LABEL => 'Label',
                    TaoMediaOntology::PROPERTY_LINK => 'dataPath/theLink',
                    TaoMediaOntology::PROPERTY_LANGUAGE => 'theLanguage',
                    TaoMediaOntology::PROPERTY_MD5 => 'theMD5',
                    TaoMediaOntology::PROPERTY_MIME_TYPE => 'theMIME',
                    TaoMediaOntology::PROPERTY_ALT_TEXT => 'theAlt',
                ];

                if ($lang !== 'http://example.com/languageId') {
                    $this->fail('Expecting properties to be fetched using the command language');
                }

                if (!isset($values[$p->getUri()])) {
                    $this->fail('Requested an unexpected resource property: ' . $p->getUri());
                }

                $ccMock = $this->createMock(core_kernel_classes_ContainerCollection::class);
                $ccMock
                    ->method('isEmpty')
                    ->willReturn(false);

                $ccMock
                    ->expects($this->once())
                    ->method('get')
                    ->with(0)
                    ->willReturn($values[$p->getUri()]);

                return $ccMock;
            });

        return $resource;
    }
}
