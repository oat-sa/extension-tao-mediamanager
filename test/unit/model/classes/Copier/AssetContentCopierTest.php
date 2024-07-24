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

namespace oat\taoMediaManager\test\unit\model;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\tao\model\resources\Contract\ClassPropertyCopierInterface;
use oat\taoMediaManager\model\classes\Copier\AssetContentCopier;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\sharedStimulus\CopyCommand;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\tao\model\resources\Contract\ClassCopierInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;

class AssetContentCopierTest extends TestCase
{
    /** @var CommandFactory */
    private $commandFactory;

    /** @var core_kernel_classes_Resource|MockObject */
    private $source;

    /** @var core_kernel_classes_Resource|MockObject */
    private $target;

    /** @var SharedStimulusResourceSpecification|MockObject */
    private $sharedStimulusSpecification;

    /** @var CopyService|MockObject */
    private $sharedStimulusCopyService;

    /** @var MediaService|MockObject */
    private $mediaService;

    /** @var MediaSource|MockObject */
    private $mediaSource;

    /** @var FileManagement|MockObject */
    private $fileManagement;

    /** @var CopyCommand|MockObject */
    private $commandMock;

    /** @var AssetContentCopier */
    private $sut;

    protected function setUp(): void
    {
        $this->source = $this->mockResource('http://test.resources/source');
        $this->target = $this->mockResource('http://test.resources/target');

        $this->commandMock = $this->createMock(CopyCommand::class);
        $this->commandFactory = $this->createMock(CommandFactory::class);
        $this->sharedStimulusCopyService = $this->createMock(CopyService::class);
        $this->mediaService = $this->createMock(MediaService::class);
        $this->fileManagement = $this->createMock(FileManagement::class);
        $this->mediaSource = $this->createMock(MediaSource::class);

        $this->sharedStimulusSpecification = $this->createMock(
            SharedStimulusResourceSpecification::class
        );

        $langPropertyMock = $this->createMock(
            core_kernel_classes_Property::class
        );

        $langPropertyMock
            ->method('getUri')
            ->willReturn(TaoMediaOntology::PROPERTY_LANGUAGE);

        $this->source
            ->method('getProperty')
            ->with(TaoMediaOntology::PROPERTY_LANGUAGE)
            ->willReturn($langPropertyMock);

        $this->sut = new AssetContentCopier(
            $this->sharedStimulusSpecification,
            $this->commandFactory,
            $this->sharedStimulusCopyService,
            $this->mediaService,
            $this->fileManagement,
            'fr-FR',
            $this->mediaSource
        );
    }

    public function testCopySharedStimulus(): void
    {
        $this->sharedStimulusSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->source)
            ->willReturn(true);

        $this->commandFactory
            ->expects($this->once())
            ->method('makeCopyCommand')
            ->withConsecutive([
                'http://test.resources/source',
                'http://test.resources/target',
                'fr-FR', // Default language for the copier
            ])
            ->willReturn($this->commandMock);

        $this->sharedStimulusCopyService
            ->expects($this->once())
            ->method('copy')
            ->with($this->commandMock);

        $this->source
            ->expects($this->once())
            ->method('getPropertyValues')
            ->with($this->callback(function ($value) {
                return ($value instanceof core_kernel_classes_Property)
                    && ($value->getUri() === TaoMediaOntology::PROPERTY_LANGUAGE);
            }))
            ->willReturn([]);

        $this->sut->copy($this->source, $this->target);
    }

    public function testCopyMediaFile(): void
    {
        $this->sharedStimulusSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->source)
            ->willReturn(false);

        $this->mediaSource
            ->method('getFileInfo')
            ->willReturn(
                [
                    'name' => 'file_name',
                    'link' => 'file_link'
                ]
            );

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn('file_contents');

        $this->fileManagement
            ->method('getFileStream')
            ->willReturn($stream);

        $this->mediaService
            ->method('editMediaInstance')
            ->willReturn(true);

        $this->sut->copy($this->source, $this->target);
    }

    public function testCopyMediaFileWillFailCannotPersistIt(): void
    {
        $this->sharedStimulusSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->source)
            ->willReturn(false);

        $this->commandFactory
            ->expects($this->never())
            ->method('makeCopyCommand');

        $this->sharedStimulusCopyService
            ->expects($this->never())
            ->method('copy');

        $this->source
            ->expects($this->never())
            ->method('getPropertyValues');

        $this->mediaSource
            ->method('getFileInfo')
            ->willReturn(
                [
                    'name' => 'file_name',
                    'link' => 'file_link'
                ]
            );

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn('file_contents');

        $this->fileManagement
            ->method('getFileStream')
            ->willReturn($stream);

        $this->mediaService
            ->method('editMediaInstance')
            ->willReturn(false);

        $this->expectExceptionMessage(
            '[link=file_link,fromLabel=,fromUri=http://test.resources/source,toLabel=,' .
            'toUri=http://test.resources/target] Failed saving asset into filesystem while copying it'
        );

        $this->sut->copy($this->source, $this->target);
    }

    public function testCommandLanguageMatchesResourceLanguage(): void
    {
        $this->sharedStimulusSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->source)
            ->willReturn(true);

        $this->commandFactory
            ->expects($this->once())
            ->method('makeCopyCommand')
            ->withConsecutive([
                'http://test.resources/source',
                'http://test.resources/target',
                'en-EN'
            ])
            ->willReturn($this->commandMock);

        $this->sharedStimulusCopyService
            ->expects($this->once())
            ->method('copy')
            ->with($this->commandMock);

        $this->source
            ->expects($this->once())
            ->method('getPropertyValues')
            ->with($this->callback(function ($value) {
                return ($value instanceof core_kernel_classes_Property)
                    && ($value->getUri() === TaoMediaOntology::PROPERTY_LANGUAGE);
            }))
            ->willReturn(['en-EN']);

        $this->sut->copy($this->source, $this->target);
    }

    public function testEmptyLanguageValueUsesDefaultLanguage(): void
    {
        $this->sharedStimulusSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->source)
            ->willReturn(true);

        $this->commandFactory
            ->expects($this->once())
            ->method('makeCopyCommand')
            ->withConsecutive([
                'http://test.resources/source',
                'http://test.resources/target',
                'fr-FR'
            ])
            ->willReturn($this->commandMock);

        $this->sharedStimulusCopyService
            ->expects($this->once())
            ->method('copy')
            ->with($this->commandMock);

        $this->source
            ->expects($this->once())
            ->method('getPropertyValues')
            ->with($this->callback(function ($value) {
                return ($value instanceof core_kernel_classes_Property)
                    && ($value->getUri() === TaoMediaOntology::PROPERTY_LANGUAGE);
            }))
            ->willReturn([' ']);

        $this->sut->copy($this->source, $this->target);
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function mockResource(string $uri): MockObject
    {
        return $this->createConfiguredMock(
            core_kernel_classes_Resource::class,
            ['exists' => true, 'getUri' => $uri]
        );
    }
}
