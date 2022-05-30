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

use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\sharedStimulus\CopyCommand;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\service\TempFileWriter;
use PHPUnit\Framework\MockObject\MockObject;
use InvalidArgumentException;

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
    ): void
    {
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
}
