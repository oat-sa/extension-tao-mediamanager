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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model;

use oat\generis\model\data\Ontology;
use oat\tao\model\resources\Command\ResourceTransferCommand;
use oat\tao\model\resources\Contract\ResourceTransferInterface;
use oat\tao\model\resources\ResourceTransferResult;
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use core_kernel_classes_Class;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use InvalidArgumentException;

class AssetClassCopierTest extends TestCase
{
    /** @var ResourceTransferInterface|MockObject */
    private $taoClassCopier;

    /** @var core_kernel_classes_Class|MockObject */
    private $source;

    /** @var core_kernel_classes_Class|MockObject */
    private $target;

    /** @var Ontology|MockObject */
    private $ontology;
    private AssetClassCopier $sut;

    protected function setUp(): void
    {
        $this->taoClassCopier = $this->createMock(ResourceTransferInterface::class);
        $this->source = $this->createMock(core_kernel_classes_Class::class);
        $this->target = $this->createMock(core_kernel_classes_Class::class);
        $this->ontology = $this->createMock(Ontology::class);

        $mediaClassSpecification = $this->createMock(MediaClassSpecification::class);
        $mediaClassSpecification
            ->method('isSatisfiedBy')
            ->willReturnCallback(function (core_kernel_classes_Class $class) {
                return in_array(
                    $class->getUri(),
                    [
                        'http://asset.root/1',
                        'http://asset.root/1/1',
                        'http://asset.root/2',
                        'http://asset.root/2/1',
                        'http://asset.root/1/c1',
                        'http://asset.root/1/c2',
                    ],
                    true
                );
            });

        $this->sut = new AssetClassCopier($mediaClassSpecification, $this->taoClassCopier, $this->ontology);
    }

    public function testTransferInvalidClassType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Class (unsupportedUri) is not supported. Only classes from ' .
            '(http://www.tao.lu/Ontologies/TAOMedia.rdf#Media)'
        );

        $this->source->method('getUri')->willReturn('unsupportedUri');

        $command = new ResourceTransferCommand(
            'unsupportedUri',
            'unsupportedUri',
            ResourceTransferCommand::ACL_KEEP_ORIGINAL,
            ResourceTransferCommand::TRANSFER_MODE_COPY
        );

        $this->ontology
            ->expects($this->once())
            ->method('getClass')
            ->willReturn($this->source);

        $this->sut->transfer($command);
    }

    public function testTransfer(): void
    {
        $this->source->method('getUri')->willReturn('http://asset.root/1/c1');
        $this->target->method('getUri')->willReturn('http://asset.root/1/c2');

        $command = new ResourceTransferCommand(
            'http://asset.root/1/c1',
            'http://asset.root/1/c2',
            ResourceTransferCommand::ACL_KEEP_ORIGINAL,
            ResourceTransferCommand::TRANSFER_MODE_COPY
        );

        $result = new ResourceTransferResult('destinationUri');

        $this->ontology
            ->expects($this->once())
            ->method('getClass')
            ->willReturn($this->source);

        $this->taoClassCopier
            ->expects($this->once())
            ->method('transfer')
            ->with($command)
            ->willReturn($result);

        $this->assertSame($result, $this->sut->transfer($command));
    }
}
